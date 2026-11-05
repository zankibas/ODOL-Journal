<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I11800_AddUserRoleMastheadUpdateEmail.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I11800_AddUserRoleMastheadUpdateEmail
 *
 * @brief Adds user role masthead update email template
 */

namespace PKP\migration\upgrade\v3_5_0;

class I11800_AddUserRoleMastheadUpdateEmail extends InstallEmailTemplates
{
    protected function getEmailTemplateKeys(): array
    {
        return [
            'USER_ROLE_MASTHEAD_UPDATE',
        ];
    }
}
