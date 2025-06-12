<?php

class Html
{
    public static function SanitizeHtml(string $html): string
    {
        $html = html_entity_decode($html);
        $html = str_replace(["\n", "\r"], ' ', $html);

        $html = preg_replace(
            [
                '~<style.*?</style>~s',
                '/<[^>]+>/',
                '/\s+/'
            ],
            ' ',
            $html
        );

        /**
         * Remove non UTF-8 characters
         */
        $html = iconv('UTF-8', 'UTF-8//IGNORE', $html);

        return $html;
    }

    public static function RedactorContentValidateAndCleanup(?string $content): string|array|null
    {
        if (!$content || Str::IsEmptyHTML($content)) {
            return '';
        }

        // Step 1: Check if there are external images, if so error out
        preg_match_all('@src="([^"]+)"@', $content, $match);
        $srcs = array_pop($match);
        // Logger::LogDebug(json_encode($match));
        foreach ($srcs as $src) {
            $src = trim($src);
            if (!str_starts_with(trim($src), 'https://' . S3_BUCKET) && !preg_match('@^https://.+\.(teleskope|affinities|officeraven|talentpeak|peoplehero)\.io@', $src) && !preg_match('@^(https?:)?//www\.youtube\.com/embed/@', $src)) {
                throw new Exception('External Link Errors', -1);
            }
        }

        // Step 2: If there are links without http or https, then add https to them.
        $content = preg_replace_callback(
            '/(<a[^>]*href=["\'])([^"\']+)(["\'][^>]*>)/i',
            function ($matches) {
                $link = $matches[2];
                if (
                    !str_starts_with($link, 'http')
                    && !str_starts_with($link, 'https')
                    && !str_starts_with($link, 'mailto:')
                    && !str_starts_with($link, '#')
                ) {
                    $link = 'https://' . $link;
                }
                return $matches[1] . $link . $matches[3];
            },
            $content
        );

        // Step 3 Add ' ' to all empty <p> tags. This will show empty <p> tags as newlines
        return preg_replace('#<p></p>#', '<p> </p>', $content);
    }

    /**
     * This is a basic markdown like conversion, not truly markdown
     * @param $html
     * @return string
     */
    public static function HtmlToReportText($html) {
        // Normalize line breaks
        $search = array(
            "/&nbsp;/","/&emsp;/", "/[ ]+/", "/<\/td[^>]*>/i",
            "/\r\n/", "/\n\s+/", "/[\n]+/", "/<\/p>/", "/<br[^>]*>/i","/<\/li>/i","/<\/tr>/i","/<table[^>]*>/i","/<\/table>/i",
            "/<li[^>]*>/i",
            "/<td[^>]*>/i",
        );
        $replace = array(
            " ", " ", " ", " ",
            "\n","\n","\n","\n","\n","\n","\n","\n","\n",
            "- ",
            "\t"
        );
        $html = preg_replace($search, $replace, $html);

        // Convert links
        $html = preg_replace_callback("/<a[^>]*href=[\"']([^\"']+)[\"'][^>]*>(.*?)<\/a>/i", function ($matches) {
            return $matches[2] . '[' . $matches[1] . ']';
        }, $html);

        // Convert images
        $html = preg_replace_callback("/<img[^>]*>/i", function ($imgTag) {
            $tag = $imgTag[0];
            preg_match('/src=["\']([^"\']+)["\']/', $tag, $srcMatch);
            preg_match('/alt=["\']([^"\']*)["\']/', $tag, $altMatch);

            $src = $srcMatch[1] ?? '';
            $alt = $altMatch[1] ?? 'Image';

            return '[' . $alt . ' - ' . $src . ']';
        }, $html);


        // Strip remaining tags
        return trim(strip_tags($html));
    }

}
