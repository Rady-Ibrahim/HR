<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\EmployeeMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeMessageController
{
    /* ─────────────────────────────────────────────
     |  Helper: resolve current employee or 404
     | ──────────────────────────────────────────── */
    private function currentEmployee(): Employee
    {
        $user     = Auth::user();
        $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            abort(404, 'لم يتم العثور على بيانات الموظف');
        }

        return $employee;
    }

    /* ─────────────────────────────────────────────
     |  POST /api/messages
     |  إرسال رسالة جديدة
     | ──────────────────────────────────────────── */
    public function send(Request $request): JsonResponse
    {
        $me = $this->currentEmployee();

        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:employees,id',
            'message'     => 'required|string|max:2000',
        ]);

        if ((int)$validated['receiver_id'] === $me->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إرسال رسالة لنفسك',
            ], 422);
        }

        $msg = EmployeeMessage::create([
            'sender_id'   => $me->id,
            'receiver_id' => $validated['receiver_id'],
            'message'     => $validated['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'data'    => $this->formatMessage($msg->load(['sender', 'receiver'])),
        ], 201);
    }

    /* ─────────────────────────────────────────────
     |  GET /api/messages
     |  قائمة المحادثات (آخر رسالة مع كل شخص)
     | ──────────────────────────────────────────── */
    public function conversations(): JsonResponse
    {
        $me = $this->currentEmployee();

        /*
         * جلب أحدث رسالة لكل "طرف ثالث" تحدّث معه الموظف
         * نستخدم sub-query لإيجاد max(id) لكل زوج (me <-> other)
         */
        $latestIds = EmployeeMessage::withTrashed(false)
            ->where(function ($q) use ($me) {
                $q->where('sender_id', $me->id)
                  ->orWhere('receiver_id', $me->id);
            })
            ->select(DB::raw('MAX(id) as last_id'))
            ->addSelect(DB::raw(
                "CASE
                    WHEN sender_id = {$me->id} THEN receiver_id
                    ELSE sender_id
                 END AS other_id"
            ))
            ->groupBy('other_id')
            ->pluck('last_id')
            ->toArray();

        $messages = EmployeeMessage::with(['sender:id,name,employee_code', 'receiver:id,name,employee_code'])
            ->whereIn('id', $latestIds)
            ->orderByDesc('created_at')
            ->get();

        // عدد الرسائل غير المقروءة من كل شخص
        $unreadCounts = EmployeeMessage::where('receiver_id', $me->id)
            ->where('is_read', false)
            ->select('sender_id', DB::raw('count(*) as cnt'))
            ->groupBy('sender_id')
            ->pluck('cnt', 'sender_id');

        $conversations = $messages->map(function ($msg) use ($me, $unreadCounts) {
            $other = ($msg->sender_id === $me->id) ? $msg->receiver : $msg->sender;
            $unread = $unreadCounts->get($other->id, 0);

            return [
                'employee'        => [
                    'id'            => $other->id,
                    'name'          => $other->name,
                    'employee_code' => $other->employee_code,
                ],
                'last_message'    => [
                    'id'        => $msg->id,
                    'message'   => $msg->message,
                    'is_mine'   => $msg->sender_id === $me->id,
                    'is_read'   => $msg->is_read,
                    'sent_at'   => $msg->created_at->toIso8601String(),
                ],
                'unread_count'    => (int) $unread,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $conversations->values(),
        ]);
    }

    /* ─────────────────────────────────────────────
     |  GET /api/messages/{employeeId}
     |  تفاصيل المحادثة مع موظف محدد
     | ──────────────────────────────────────────── */
    public function conversation(Request $request, int $employeeId): JsonResponse
    {
        $me    = $this->currentEmployee();
        $other = Employee::findOrFail($employeeId);

        $perPage = (int) $request->get('per_page', 30);
        $perPage = min($perPage, 100);

        $messages = EmployeeMessage::conversation($me->id, $employeeId)
            ->with(['sender:id,name,employee_code', 'receiver:id,name,employee_code'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // تحديد الرسائل كمقروءة تلقائياً
        EmployeeMessage::where('sender_id', $employeeId)
            ->where('receiver_id', $me->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $formatted = $messages->getCollection()->map(
            fn($msg) => $this->formatMessage($msg, $me->id)
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'employee' => [
                    'id'            => $other->id,
                    'name'          => $other->name,
                    'employee_code' => $other->employee_code,
                    'department'    => $other->department,
                    'position'      => $other->position,
                ],
                'messages'   => $formatted->values(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page'    => $messages->lastPage(),
                    'per_page'     => $messages->perPage(),
                    'total'        => $messages->total(),
                    'has_more'     => $messages->hasMorePages(),
                ],
            ],
        ]);
    }

    /* ─────────────────────────────────────────────
     |  PUT /api/messages/{employeeId}/read
     |  تحديد رسائل شخص معين كمقروءة
     | ──────────────────────────────────────────── */
    public function markRead(int $employeeId): JsonResponse
    {
        $me = $this->currentEmployee();

        $updated = EmployeeMessage::where('sender_id', $employeeId)
            ->where('receiver_id', $me->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الرسائل كمقروءة',
            'data'    => ['updated_count' => $updated],
        ]);
    }

    /* ─────────────────────────────────────────────
     |  GET /api/messages/unread-count
     |  عدد الرسائل غير المقروءة الإجمالي
     | ──────────────────────────────────────────── */
    public function unreadCount(): JsonResponse
    {
        $me    = $this->currentEmployee();
        $count = EmployeeMessage::where('receiver_id', $me->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data'    => ['unread_count' => $count],
        ]);
    }

    /* ─────────────────────────────────────────────
     |  DELETE /api/messages/{messageId}
     |  حذف رسالة (المُرسِل فقط)
     | ──────────────────────────────────────────── */
    public function destroy(int $messageId): JsonResponse
    {
        $me  = $this->currentEmployee();
        $msg = EmployeeMessage::where('id', $messageId)
            ->where('sender_id', $me->id)
            ->firstOrFail();

        $msg->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الرسالة',
        ]);
    }

    /* ─────────────────────────────────────────────
     |  Private: format a single message
     | ──────────────────────────────────────────── */
    private function formatMessage(EmployeeMessage $msg, ?int $myId = null): array
    {
        $myId = $myId ?? Auth::user()?->employee?->id;

        return [
            'id'       => $msg->id,
            'message'  => $msg->message,
            'is_mine'  => $msg->sender_id === $myId,
            'is_read'  => $msg->is_read,
            'read_at'  => $msg->read_at?->toIso8601String(),
            'sent_at'  => $msg->created_at->toIso8601String(),
            'sender'   => $msg->sender ? [
                'id'            => $msg->sender->id,
                'name'          => $msg->sender->name,
                'employee_code' => $msg->sender->employee_code,
            ] : null,
            'receiver' => $msg->receiver ? [
                'id'            => $msg->receiver->id,
                'name'          => $msg->receiver->name,
                'employee_code' => $msg->receiver->employee_code,
            ] : null,
        ];
    }
}
