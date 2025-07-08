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

        public function getAllPaginated($perPage = 3)
    {
        return Campaign::with('users')->latest()->paginate($perPage);
    }

    public function getFilteredPaginated(array $filters = [])
    {
        $query = Campaign::with('users');

        // 🔍 Search by name
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // 🔽 Filter by status
        if ($filters['status'] === 'sent') {
            $query->where('sent', true);
        } elseif ($filters['status'] === 'scheduled') {
            $query->where('sent', false);
        }

        // 🔃 Sort by scheduled_at
        if (in_array($filters['sort'], ['asc', 'desc'])) {
            $query->orderBy('scheduled_at', $filters['sort']);
        } else {
            $query->orderBy('scheduled_at', 'desc');
        }

        return $query->paginate(3)->appends($filters); // retain query strings in pagination
    }


}
