<?php
/**
 * Display standard error page
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

print "<h1>An error occurred</h1>\n";
print $this->htmlTag('h2', $this->message);

if (\Library\Application::isDevelopment()) {
    $exception = $this->exception;
    if ($exception) {
        print "<h3>Exception Message trace:</h3>\n";

        while ($exception) {
            print $this->htmlTag(
                'p',
                '<strong>Message:</strong> ' . $this->escapeHtml($exception->getMessage())
            );
            print $this->htmlTag(
                'p',
                sprintf(
                    '<strong>Source:</strong> %s, line %d',
                    $this->escapeHtml($exception->getFile()),
                    $this->escapeHtml($exception->getLine())
                )
            );
            $exception = $exception->getPrevious();
        }

// TODO: hide details for login controller
//     // The additional debug information below might contain sensitive data.
//     if ($this->request->getParam('controller') == 'login') {
//         print 'Details hidden for security reasons.';
//         return;
//     }

        print "<h3>Stack trace:</h3>\n";
        print $this->htmlTag('pre', $this->escapeHtml($this->exception->getTraceAsString()));
    }

// TODO: display request parameters
//     print "<h3>Request Parameters:</h3>\n";
//     $params = $this->request->getParams();
//     // If the Xdebug extension is loaded, var_dump() is overloaded with Xdebug's
//     // version that formats and escapes the output. Starting with Xdebug 2.1,
//     // this feature can be disabled, so this needs to be checked too. For Xdebug
//     // 2.1+, ini_get() returns '0' (disabled) or a nonempty string (enabled),
//     // while older versions always return '' (always enabled).
//     $prettyprint = ini_get('xdebug.overload_var_dump');
//     if (extension_loaded('xdebug') and ($prettyprint === '' or !empty($prettyprint))) {
//         // Xdebug extension formats and escapes the output.
//         var_dump($params);
//     } else {
//         // Standard output
//         print $this->htmlTag('pre', $this->escape(var_export($params, true)));
//     }
} else {
    print "<p class='textcenter'>Details can be found in the web server error log.</p>\n";
}
