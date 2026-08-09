<?php

namespace App\Enums;

enum Permission: string
{
    case ManageGifts = 'manage_gifts';
    case ManageContributions = 'manage_contributions';
    case ManageBankTransactions = 'manage_bank_transactions';
    case ManageSettings = 'manage_settings';
}
