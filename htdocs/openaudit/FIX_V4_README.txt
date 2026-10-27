OpenAudit V4 - Navigation fix

Root cause: include_menu_array.php stores &amp; in its link values,
while include.php escaped these values a second time with htmlspecialchars().
The generated HTML therefore contained &amp;amp;, which browsers decoded
to a literal &amp; inside the URL, producing the invalid amp;view parameter.

Fix: oaMenuHref() in include.php decodes HTML entities before encoding
the URL for an HTML attribute. Applied to all top-level, second-level
and third-level menu links, covering all modules using the shared menu.

No blanket replacement of &amp; was performed, because it is valid in HTML.
The PHP syntax was checked; application/database integration tests were not possible.

Replace include.php from this archive and clear browser cache.
Other V3 fixes (including audit.ps1) remain in the package.
