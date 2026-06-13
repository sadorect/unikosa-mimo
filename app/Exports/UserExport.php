<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?int $userId = null,
    ) {}

    public function collection()
    {
        $query = User::with(['graduatingSet', 'chapter', 'payments'])
            ->select([
                'id', 'name', 'email', 'phone', 'date_of_birth', 'gender',
                'graduating_set_id', 'house', 'country', 'city', 'profession',
                'bio', 'skills', 'social_links', 'status', 'chapter_id',
                'imported', 'account_claimed', 'created_at',
            ]);

        if ($this->userId) {
            $query->where('id', $this->userId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Name', 'Email', 'Phone', 'Date of Birth', 'Gender',
            'Graduating Set', 'House', 'Country', 'City', 'Profession',
            'Bio', 'Skills', 'Social Links', 'Status', 'Chapter',
            'Imported', 'Account Claimed', 'Registered At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone,
            $user->date_of_birth?->format('Y-m-d'),
            $user->gender,
            $user->graduatingSet?->name,
            $user->house,
            $user->country,
            $user->city,
            $user->profession,
            $user->bio,
            $user->skills ? implode(', ', $user->skills) : '',
            $user->social_links ? json_encode($user->social_links) : '',
            $user->status,
            $user->chapter?->name,
            $user->imported ? 'Yes' : 'No',
            $user->account_claimed ? 'Yes' : 'No',
            $user->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
