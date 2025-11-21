<?php

namespace Kameleoon\Network\Cookie;

interface CookieManager
{
    /**
     * Gets or adds a visitor code to the provided HTTP request and response.
     *
     * This method searches for a visitor code in the provided HTTP request and response objects. If a
     * visitor code is not found and a defaultVisitorCode is not provided, it generates a random visitor
     * code. If a defaultVisitorCode is provided, it is used as the visitor code when no existing code
     * is found.
     *
     * Usage example:
     *
     * // Generate a random visitor code if not found in cookies
     * $visitorCode = $cookieManager->getOrAdd();
     *
     * // Use a default visitor code if not found in cookies
     * $visitorCode = $cookieManager->getOrAdd("defaultCode");
     *
     * @param string $defaultVisitorCode The default visitor code to use when no existing code is found.
     * @return string The retrieved or generated visitor code.
     */
    public function getOrAdd(?string $defaultVisitorCode = null);

    /**
     * Updates the visitor code in the cookies of the HTTP response based on legal consent.
     *
     * This method updates the visitor code in the cookies of the provided HTTP response
     * based on the specified legal consent. If the legal consent is granted, it adds the
     * provided visitor code to the response cookies using the `add` method. If the legal
     * consent is denied or revoked, it does not remove any existing visitor code from the response cookies.
     *
     * Usage example:
     *
     * // Update the visitor code based on legal consent
     * $legalConsent = true; // Set to true if consent is granted, false otherwise
     * $visitorCode = "visitor123";
     * $cookieManager->update($legalConsent, $visitorCode);
     *
     * @param string $visitorCode  The visitor code to be added to the response cookies if legal consent is granted.
     * @param bool   $legalConsent Indicates whether legal consent is granted (true) or denied/revoked (false).
     */
    public function update(string $visitorCode, bool $legalConsent);
}
