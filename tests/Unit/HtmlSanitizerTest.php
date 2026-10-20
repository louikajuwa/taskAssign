<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_script_with_content(): void
    {
        $out = HtmlSanitizer::clean('<p>a</p><script>alert(1)</script><p>b</p>');

        $this->assertSame('<p>a</p><p>b</p>', $out);
    }

    public function test_strips_event_handlers(): void
    {
        $out = HtmlSanitizer::clean('<p onclick="x()" onmouseover="y()">hi</p><img src="/storage/uploads/a.png" onerror="alert(1)">');

        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('onmouseover', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringContainsString('<img src="/storage/uploads/a.png">', $out);
    }

    public function test_removes_javascript_hrefs_including_obfuscated(): void
    {
        foreach ([
            '<a href="javascript:alert(1)">x</a>',
            '<a href="JaVaScRiPt:alert(1)">x</a>',
            "<a href=\"java\tscript:alert(1)\">x</a>",
            '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>',
            '<a href="vbscript:x">x</a>',
        ] as $html) {
            $out = HtmlSanitizer::clean($html);
            $this->assertStringNotContainsString('href', $out, $html);
            $this->assertStringContainsString('x', $out);
        }
    }

    public function test_safe_links_get_rel_and_target(): void
    {
        $out = HtmlSanitizer::clean('<a href="https://example.com/p?a=1" style="color:red">go</a>');

        $this->assertStringContainsString('href="https://example.com/p?a=1"', $out);
        $this->assertStringContainsString('rel="noopener noreferrer"', $out);
        $this->assertStringContainsString('target="_blank"', $out);
        $this->assertStringNotContainsString('style', $out);
        $this->assertStringContainsString('mailto:', HtmlSanitizer::clean('<a href="mailto:a@b.co">m</a>'));
    }

    public function test_strips_style_attribute_and_tag(): void
    {
        $out = HtmlSanitizer::clean('<style>body{display:none}</style><p style="position:fixed">t</p>');

        $this->assertSame('<p>t</p>', $out);
    }

    public function test_strips_iframe_object_svg_form(): void
    {
        $out = HtmlSanitizer::clean('<iframe src="//evil"></iframe><object data="x"></object><svg onload="x()"><circle/></svg><form action="/x"><input></form><p>ok</p>');

        $this->assertSame('<p>ok</p>', $out);
    }

    public function test_img_with_bad_src_is_dropped(): void
    {
        $this->assertSame('', HtmlSanitizer::clean('<img src="javascript:alert(1)">'));
        $this->assertSame('', HtmlSanitizer::clean('<img src="data:image/svg+xml;base64,AAA">'));
        $this->assertSame('', HtmlSanitizer::clean('<img src="/storage/../etc/passwd">'));
        $this->assertStringContainsString('src="https://x.test/a.png"', HtmlSanitizer::clean('<img src="https://x.test/a.png">'));
    }

    public function test_keeps_allowed_tags_and_ql_classes_only(): void
    {
        $html = '<h1>T</h1><p><strong>b</strong> <em>i</em> <u>u</u> <s>s</s></p><ul><li data-list="bullet" class="ql-indent-1 evil">x</li></ul><ol><li>y</li></ol><blockquote>q</blockquote><pre class="ql-syntax">c</pre><p><br></p>';
        $out = HtmlSanitizer::clean($html);

        foreach (['<h1>T</h1>', '<strong>b</strong>', '<em>i</em>', '<u>u</u>', '<s>s</s>', '<blockquote>q</blockquote>', '<ol><li>y</li></ol>', 'class="ql-syntax"', '<br>'] as $keep) {
            $this->assertStringContainsString($keep, $out);
        }
        $this->assertStringContainsString('class="ql-indent-1"', $out);
        $this->assertStringContainsString('data-list="bullet"', $out);
        $this->assertStringNotContainsString('evil', $out);
    }

    public function test_unknown_tags_are_unwrapped_keeping_text(): void
    {
        $this->assertSame('<p>hello</p>', HtmlSanitizer::clean('<p><font color="red">hel<marquee>lo</marquee></font></p>'));
    }

    public function test_preserves_utf8(): void
    {
        $out = HtmlSanitizer::clean('<p>Café ✓ 日本 😀</p>');

        $this->assertSame('<p>Café ✓ 日本 😀</p>', $out);
    }

    public function test_text_is_escaped_not_executed(): void
    {
        $out = HtmlSanitizer::clean('<p>1 < 2 &amp; "q"</p>');

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringContainsString('&amp;', $out);
    }

    public function test_entity_encoded_script_stays_inert_text(): void
    {
        $out = HtmlSanitizer::clean('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    public function test_comments_and_cdata_are_removed(): void
    {
        $out = HtmlSanitizer::clean('<p>a<!-- <script>x</script> --></p><!--[if IE]><script>x</script><![endif]-->');

        $this->assertSame('<p>a</p>', $out);
    }

    public function test_empty_and_whitespace(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(''));
        $this->assertSame('', HtmlSanitizer::clean("  \n "));
    }

    public function test_nested_script_tricks(): void
    {
        $out = HtmlSanitizer::clean('<scr<script>ipt>alert(1)</scr</script>ipt>');

        $this->assertStringNotContainsString('<script', strtolower($out));
    }
}
