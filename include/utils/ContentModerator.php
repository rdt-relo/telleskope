<?php

class ContentModerator
{
    /**
     * @param ...$strings
     * @return void
     * @throws ContentModeratorException
     */
    public static function CheckBlockedWords(...$strings): void
    {
        global $_COMPANY;

        if (!$_COMPANY->getAppCustomization()['content_moderator']['enabled']) {
            return;
        }

        $blocked_keywords = BlockedKeyword::GetAllBlockedKeywords();
        $blocked_keywords = array_map(function (BlockedKeyword $keyword) {
            return $keyword->val('blocked_keyword');
        }, $blocked_keywords);

        if (empty($blocked_keywords)) {
            return;
        }

        $content_blocked_keywords = [];
        foreach ($strings as $str) {
            $str = Html::SanitizeHtml(strtolower($str));
            // Use mb_split for multibyte characters:
            if (mb_strlen($str) !== strlen($str)) {
                $words = mb_split('\s+', $str);
            } else {
                $words = preg_split('/\s+/', $str);
            }

            $matches = array_intersect($blocked_keywords, $words);

            if ($matches) { // Found a match
                $content_blocked_keywords = array_merge($content_blocked_keywords, $matches);
            }
        }

        $content_blocked_keywords = array_unique($content_blocked_keywords);

        if (!$content_blocked_keywords) {
            return;
        }

        throw new ContentModeratorException(sprintf(
            gettext('Please remove these blocked keywords from the content - %s'),
            implode(', ', $content_blocked_keywords)
        ));
    }
}

class ContentModeratorException extends TeleskopeException
{
}
