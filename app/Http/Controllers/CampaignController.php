<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Repositories\CampaignRepository;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use App\Services\CampaignService;
use App\Services\BirthdayEmailService;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;
    protected BirthdayEmailService $birthdayService;
    protected CampaignRepository $campaignRepository;

    public function __construct(CampaignService $campaignService, BirthdayEmailService $birthdayService, CampaignRepository $campaignRepository)
    {
        $this->campaignService = $campaignService;
        $this->birthdayService = $birthdayService;
        $this->campaignRepository = $campaignRepository;
    }

    public function index()
    {
        $this->birthdayService->send();
        $this->campaignService->processDueCampaigns();

        // $campaigns = Campaign::with('users')->latest()->get();
        $campaigns = $this->campaignRepository->getAll();
        return view('users.campaigns_index', compact('campaigns'));
    }

    public function create()
    {
        $users = User::role('user')->get();
        $templates = EmailTemplate::all();
        return view('users.campaigns_create', compact('users', 'templates'));
    }

    public function store(StoreCampaignRequest $request)
    {
        $campaign = $this->campaignService->createCampaign($request->all());

        return redirect()->route('campaigns.index')
            ->with('success', $request->scheduled_at ? 'Campaign scheduled.' : 'Campaign created and emails queued.');
    }

    public function edit(Campaign $campaign)
    {
        $templates = EmailTemplate::all();
        $users = User::role('user')->get();
        $campaign->load('users');

        return view('users.campaigns_create', compact('campaign', 'templates', 'users'));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $this->campaignService->updateCampaign($campaign, $request->all());

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        // $campaign->delete();
        $this->campaignRepository->delete($campaign);
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('users');
        return view('users.campaigns_show', compact('campaign'));
    }

    public function sendNow(Campaign $campaign)
    {
        $result = $this->campaignService->sendNow($campaign);

        if ($result === false) {
            return redirect()->back()->with('error', 'Campaign already sent.');
        } elseif ($result === 'template_missing') {
            return redirect()->back()->with('error', 'The assigned email template no longer exists. Please update the campaign.');
        }

        return redirect()->back()->with('success', 'Campaign sent successfully.');
    }

    public function reset(Campaign $campaign)
    {
        $campaign->update(['sent' => false]);
        return redirect()->back()->with('success', 'Campaign send status reset.');
    }

    public function syncTemplate(Campaign $campaign)
    {
        $template = \App\Models\EmailTemplate::find($campaign->email_template_id);

        if (!$template) {
            return redirect()->back()->with('error', 'Email template no longer exists.');
        }

        $campaign->update([
            'subject' => $template->subject,
            'content' => $template->content,
        ]);

        return redirect()->back()->with('success', 'Campaign synced with latest email template.');
    }

}
