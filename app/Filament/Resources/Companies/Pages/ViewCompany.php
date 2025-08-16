<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Attach Admin
            Action::make('attachAdmin')
                ->label('Attach Admin')
                ->visible(fn($record) => !$record->admin_id)
                ->icon('heroicon-o-user-plus')
                ->color('secondary')
                ->schema([
                    TextInput::make('first_name')->required(),
                    TextInput::make('last_name')->required(),
                    TextInput::make('email')->email()->required(),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(),
                ])
                ->action(function (array $data, $record) {
                    DB::beginTransaction();
                    $admin = User::create([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'password' => $data['password'], // will be hashed automatically
                        'role' => UserRole::ADMIN,
                        'company_id' => $record->id,
                    ]);

                    $record->admin_id = $admin->id;
                    $record->save();
                    DB::commit();

                    Notification::make()
                        ->title('Admin created successfully')
                        ->success()
                        ->send();
                }),

            // Edit Admin
            Action::make('editAdmin')
                ->label('Edit Admin')
                ->visible(fn($record) => $record->admin_id)
                ->icon('heroicon-o-user-circle')
                ->color('gray')
                ->schema(function ($record) {
                    $admin = $record->admin; // assuming relation `admin()` exists
                    return [
                        TextInput::make('first_name')
                            ->required()
                            ->default($admin->first_name),
                        TextInput::make('last_name')
                            ->required()
                            ->default($admin->last_name),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->default($admin->email),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($state) => $state ?: null), // only update if filled
                    ];
                })
                ->action(function (array $data, $record) {
                    $admin = $record->admin;

                    $admin->first_name = $data['first_name'];
                    $admin->last_name = $data['last_name'];
                    $admin->email = $data['email'];

                    if (!empty($data['password'])) {
                        $admin->password = $data['password']; // hashed automatically
                    }

                    $admin->save();

                    Notification::make()
                        ->title('Admin updated successfully')
                        ->success()
                        ->send();
                }),

            // Edit Company
            EditAction::make()
            ->icon(Heroicon::PencilSquare)
            ->label(fn($record) => "Edit " . $record->name),
        ];
    }
}

