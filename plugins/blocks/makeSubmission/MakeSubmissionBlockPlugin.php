<?php

/**
 * @file plugins/blocks/makeSubmission/MakeSubmissionBlockPlugin.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MakeSubmissionBlockPlugin
 * @ingroup plugins_blocks_makeSubmission
 *
 * @brief Class for the "Make a Submission" block plugin
 */

namespace APP\plugins\blocks\makeSubmission;

use PKP\plugins\BlockPlugin;

class MakeSubmissionBlockPlugin extends BlockPlugin
{
    /**
     * Get the display name of this plugin.
     */
    public function getDisplayName(): string
    {
        return __('plugins.block.makeSubmission.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription(): string
    {
        return __('plugins.block.makeSubmission.description');
    }

    /**
     * @copydoc BlockPlugin::getContents()
     *
     * @param null|mixed $request
     */
    public function getContents($templateMgr, $request = null)
    {
        $context = $request->getContext();
        if (!$context) {
            return '';
        }
        return parent::getContents($templateMgr);
    }
}
