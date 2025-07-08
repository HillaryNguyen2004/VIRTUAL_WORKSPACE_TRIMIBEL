<?php
namespace App\Repositories;

use App\Models\EmailTemplate;

class EmailTemplateRepository
{
    public function create(array $data): EmailTemplate
    {
        return EmailTemplate::create($data);
    }

    public function update(EmailTemplate $template, array $data): bool
    {
        return $template->update($data);
    }

    public function delete(EmailTemplate $template): ?bool
    {
        return $template->delete();
    }

    public function paginate(int $perPage = 3)
    {
        return EmailTemplate::latest()->paginate($perPage);
    }

    public function getFilteredPaginated(array $filters = [], int $perPage = 3)
    {
        $query = EmailTemplate::query();

        // 🔍 Search by name or subject
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('subject', 'like', '%' . $filters['search'] . '%');
            });
        }

        // 🔃 Sort by name or created_at
        $sortField = in_array($filters['sort_by'] ?? '', ['name', 'created_at']) ? $filters['sort_by'] : 'created_at';
        $sortDir = in_array($filters['sort_dir'] ?? '', ['asc', 'desc']) ? $filters['sort_dir'] : 'desc';

        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage)->appends($filters);
    }
}
