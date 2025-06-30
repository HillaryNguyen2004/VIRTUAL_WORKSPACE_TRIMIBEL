<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Mail\CampaignEmail;
use Illuminate\Support\Facades\Mail;


class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::with('users')->latest()->get();
        return view('users.campaigns_index', compact('campaigns'));
    }

    public function create()
    {
        $users = User::role('user')->get(); // Only assign regular users
        $templates = EmailTemplate::all();
        return view('users.campaigns_create', compact('users', 'templates'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'subject' => 'nullable|string|max:255',
    //         'content' => 'nullable|string',
    //         'scheduled_at' => 'nullable|date',
    //         'users' => 'nullable|array',
    //         'users.*' => 'exists:users,id',
    //     ]);

    //     $campaign = Campaign::create([
    //         'name' => $request->name,
    //         'subject' => $request->subject,
    //         'content' => $request->content,
    //         'scheduled_at' => $request->scheduled_at,
    //     ]);

    //     if ($request->has('users')) {
    //         $campaign->users()->attach($request->users);
    //     }

    //     return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
        ]);

        // Use template content if selected
        if ($request->email_template_id) {
            $template = EmailTemplate::find($request->email_template_id);
            $subject = $template->subject;
            $content = $template->content;
        } else {
            $subject = $request->subject;
            $content = $request->content;
        }

        $campaign = Campaign::create([
            'name' => $request->name,
            'subject' => $subject,
            'content' => $content,
            'scheduled_at' => $request->scheduled_at,
            'email_template_id' => $request->email_template_id,
        ]);

        // Attach users to campaign
        if ($request->has('users')) {
            $campaign->users()->attach($request->users);

            foreach ($request->users as $userId) {
                $user = User::find($userId);
                $personalizedContent = str_replace([
                    '{first_name}',
                    '{total_of_times_late}',
                    '{site_title}'
                ], [
                    $user->name,
                    $user->times_late ?? 0,
                    config('app.name'),
                ], $content);

                Mail::to($user->email)->send(new CampaignEmail($subject, $personalizedContent));
            }
        }

        return redirect()->route('campaigns.index')->with('success', 'Campaign created and emails sent.');
    }

    public function edit(Campaign $campaign)
    {
        $users = User::role('user')->get();
        return view('users.campaigns_edit', compact('campaign', 'users'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

        $campaign->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $request->content,
            'scheduled_at' => $request->scheduled_at,
        ]);

        $campaign->users()->sync($request->users ?? []);

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('users');
        return view('users.campaigns_show', compact('campaign'));
    }
}
