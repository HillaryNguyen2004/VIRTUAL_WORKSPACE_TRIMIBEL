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

    public function index(Request $request)
    {
        $this->campaignService->runScheduledTasks();
        $filters = $this->campaignService->extractFilters($request);
        $campaigns = $this->campaignService->getFilteredCampaigns($filters);
        return view('users.campaigns_index', compact('campaigns', 'filters'));
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
            ->with('success', $request->scheduled_at ? __('messages.campaign_scheduled') : __('messages.campaign_created_and_queued'));
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

        return redirect()->route('campaigns.index')->with('success', __('messages.campaign_updated'));
    }

    public function destroy(Campaign $campaign)
    {
        $this->campaignRepository->delete($campaign);
        return redirect()->route('campaigns.index')->with('success', __('messages.campaign_deleted'));
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
            return redirect()->back()->with('error', __('messages.campaign_already_sent'));
        } elseif ($result === 'template_missing') {
            return redirect()->back()->with('error', __('messages.template_missing'));
        }

        return redirect()->back()->with('success', __('messages.campaign_sent_successfully'));
    }

    public function reset(Campaign $campaign)
    {
        $campaign->update(['sent' => false]);
        return redirect()->back()->with('success', __('messages.campaign_reset'));
    }
}
