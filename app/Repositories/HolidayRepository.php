<?php
namespace App\Repositories;

use App\Models\Holiday;

class HolidayRepository
{
    public function getAll()
    {
        return Holiday::latest()->get();
    }

    public function create(array $data)
    {
        return Holiday::create($data);
    }

    public function update(Holiday $holiday, array $data)
    {
        $holiday->update($data);
        return $holiday;
    }

    public function delete(Holiday $holiday)
    {
        return $holiday->delete();
    }

    public function getAllPaginated($perPage = 10)
    {
        return Holiday::latest()->paginate($perPage);
    }

    public function getFilteredPaginated(array $filters = [])
    {
        $query = Holiday::query();

        // 🔍 Search by title
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        // 📅 Filter by year
        if (!empty($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        // 🔃 Sort by start_date
        if (in_array($filters['sort'], ['asc', 'desc'])) {
            $query->orderBy('start_date', $filters['sort']);
        } else {
            $query->orderBy('start_date', 'desc');
        }

        return $query->paginate(10)->appends($filters);
    }

    public function getUpcoming($limit = 10)
    {
        return Holiday::where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
