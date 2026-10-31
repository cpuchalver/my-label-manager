<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LabelRole: string implements HasLabel
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Viewer = 'viewer';

    /**
     * The roles that can be given to a member directly. Ownership is only obtained by a transfer.
     *
     * @return array<string, string>
     */
    public static function assignableOptions(): array
    {
        return [
            self::Admin->value => self::Admin->getLabel(),
            self::Manager->value => self::Manager->getLabel(),
            self::Viewer->value => self::Viewer->getLabel(),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Admin => __('Administrator'),
            self::Manager => __('Manager'),
            self::Viewer => __('Viewer'),
        };
    }

    /** @return array<int, LabelPermission> */
    public function permissions(): array
    {
        $viewerPermissions = [
            LabelPermission::ViewCatalog,
        ];

        $managerPermissions = [
            ...$viewerPermissions,
            LabelPermission::ManageCatalog,
            LabelPermission::ImportCatalog,
        ];

        $adminPermissions = [
            ...$managerPermissions,
            LabelPermission::DeleteCatalog,
            LabelPermission::PublishContracts,
            LabelPermission::InvitePartners,
            LabelPermission::ManageMembers,
            LabelPermission::ManageSettings,
            LabelPermission::ViewAuditLog,
        ];

        return match ($this) {
            self::Viewer => $viewerPermissions,
            self::Manager => $managerPermissions,
            self::Admin => $adminPermissions,
            self::Owner => [
                ...$adminPermissions,
                LabelPermission::TransferOwnership,
                LabelPermission::CloseLabel,
            ],
        };
    }
}
