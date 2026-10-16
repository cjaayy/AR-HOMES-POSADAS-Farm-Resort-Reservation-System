<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffTaskController extends Controller
{
    public function getTasks(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $hasTable = DB::select("SHOW TABLES LIKE 'staff_tasks'");
            if (empty($hasTable)) {
                return response()->json([
                    'success' => true,
                    'tasks' => []
                ]);
            }

            $tasks = DB::table('staff_tasks')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'tasks' => $tasks
            ]);
        } catch (Exception $e) {
            Log::error('Staff Get Tasks Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function handleAction(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $action = $request->input('action');
        $staffId = session('staff_id', session('admin_id', 0));
        $staffName = session('staff_full_name', session('admin_full_name', 'Staff Member'));

        try {
            switch ($action) {
                case 'create':
                    $title = trim($request->input('title', ''));
                    if (empty($title)) {
                        return response()->json(['success' => false, 'message' => 'Title is required'], 400);
                    }

                    $id = DB::table('staff_tasks')->insertGetId([
                        'staff_id' => $staffId,
                        'staff_name' => $staffName,
                        'title' => $title,
                        'description' => trim($request->input('description', '')),
                        'priority' => $request->input('priority', 'medium'),
                        'status' => $request->input('status', 'todo'),
                        'due_date' => $request->input('dueDate') ?: null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Task created successfully',
                        'task_id' => $id
                    ]);

                case 'update':
                    $taskId = $request->input('id');
                    DB::table('staff_tasks')->where('id', $taskId)->update([
                        'status' => $request->input('status', 'todo'),
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Task updated successfully'
                    ]);

                case 'delete':
                    $taskId = $request->input('id');
                    DB::table('staff_tasks')->where('id', $taskId)->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Task deleted successfully'
                    ]);

                default:
                    return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
            }
        } catch (Exception $e) {
            Log::error('Staff Task Action Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
