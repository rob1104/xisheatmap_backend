<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $search;
    protected $role;

    public function __construct($search = null, $role = null)
    {
        $this->search = $search;
        $this->role = $role;
    }

    public function query()
    {
        $query = User::with('parent:id,name,role')
            ->withCount(['children', 'ines']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->role) {
            $query->where('role', $this->role);
        }

        return $query->orderBy('role')->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Rol',
            'Jefe Directo',
            'Equipo (Cant.)',
            'Capturas INEs',
            'Fecha de Creación',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->role->value ?? $user->role,
            $user->parent ? $user->parent->name : 'Sin jefe',
            $user->children_count,
            $user->ines_count,
            $user->created_at ? $user->created_at->format('d/m/Y H:i A') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '4F46E5']]],
        ];
    }
}
