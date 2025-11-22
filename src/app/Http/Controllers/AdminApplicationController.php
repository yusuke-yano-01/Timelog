<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Actor;
use App\Models\Application;

class AdminApplicationController extends Controller
{
    /**
     * 申請一覧画面を表示（管理者用）
     */
    public function applicationList(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = Application::with(['user', 'time'])
            ->whereHas('user', function($q) {
                $q->where('registeredflg', true)
                  ->where('actor_id', '!=', Actor::ADMIN_ID);
            })
            ->orderBy('date', 'desc');

        if ($status === 'pending') {
            $query->where('application_flg', Application::STATUS_PENDING);
        } else {
            $query->where('application_flg', Application::STATUS_APPROVED);
        }

        $applications = $query->get();

        return view('admin.application_list', compact('applications', 'status'));
    }

    /**
     * 申請詳細画面を表示（管理者用）
     */
    public function applicationDetail(Request $request, $id)
    {
        if (Auth::user()->actor_id !== Actor::ADMIN_ID) {
            abort(403, 'Unauthorized access.');
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            abort(404, 'Staff not found.');
        }

        $year = $request->get('year');
        $month = $request->get('month');
        $day = $request->get('day');

        if (!$year || !$month || !$day) {
            abort(400, 'Date parameters (year, month, day) are required.');
        }
        
        $date = Carbon::create($year, $month, $day);
        
        $application = Application::where('user_id', $targetUser->id)
            ->where('date', $date->format('Y-m-d'))
            ->with(['time', 'breaktimes'])
            ->first();

        if (!$application) {
            abort(404, 'Application not found.');
        }

        $isPending = $application->application_flg === Application::STATUS_PENDING;
        
        return view('admin.application_detail', compact(
            'application',
            'date',
            'targetUser',
            'isPending'
        ));
    }
}

