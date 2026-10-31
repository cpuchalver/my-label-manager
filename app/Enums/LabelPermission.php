<?php

namespace App\Enums;

enum LabelPermission: string
{
    case ViewCatalog = 'catalog.view';
    case ManageCatalog = 'catalog.manage';
    case ImportCatalog = 'catalog.import';
    case DeleteCatalog = 'catalog.delete';
    case PublishContracts = 'contracts.publish';
    case InvitePartners = 'partners.invite';
    case ManageMembers = 'members.manage';
    case ManageSettings = 'settings.manage';
    case ViewAuditLog = 'audit.view';
    case TransferOwnership = 'label.transfer';
    case CloseLabel = 'label.close';
}
