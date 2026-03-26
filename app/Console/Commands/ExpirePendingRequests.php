<?php

namespace App\Console\Commands;

use App\Models\Request as RequestModel;
use App\Notifications\RequestRejected;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ExpirePendingRequests extends Command
{
    protected $signature = 'requests:expire-pending';

    protected $description = 'Expire pending requests after 24 hours';

    public function handle(): int
    {
        $expiredRequests = RequestModel::where('status', 'pending')
            ->where('pending_until', '<', now())
            ->with('user')
            ->get();

        $count = $expiredRequests->count();

        foreach ($expiredRequests as $request) {
            $request->update(['status' => 'rejected']);

            Notification::send($request->user, new RequestRejected($request));
        }

        if ($count > 0) {
            $this->info("Expired {$count} pending request(s).");
        } else {
            $this->info('No pending requests to expire.');
        }

        return Command::SUCCESS;
    }
}
