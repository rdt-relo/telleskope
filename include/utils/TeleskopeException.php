<?php

/**
 * TeleskopeException provides a utility exception class that can be handled by converting exception into an
 * appropriate response for Mobile API or Ajax
 */
class TeleskopeException extends Exception
{
    public static function CustomExceptionHandler(Throwable $e): void
    {
        if (Http::IsMobileApiRequest()) {
            exit (MobileAppApi::buildApiResponseAsJson(
                success: 0,
                message: $e->getMessage()
            ));
        }

        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            $e->getMessage(),
            gettext('Error')
        );
    }
}