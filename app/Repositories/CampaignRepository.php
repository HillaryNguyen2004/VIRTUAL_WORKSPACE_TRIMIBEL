<?php
namespace App\Repositories;

use App\Models\Campaign;

class CampaignRepository
{
    public function getAll()
    {
        return Campaign::with('users')->latest()->get();
    }

    public function getDueCampaigns()
    {
        return Campaign::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('sent', false)
            ->with('users')
            ->get();
    }

    public function create(array $data)
    {
        return Campaign::create($data);
    }

    public function update(Campaign $campaign, array $data)
    {
        $campaign->update($data);
        return $campaign;
    }

    public function delete(Campaign $campaign)
    {
        return $campaign->delete();
    }
}
