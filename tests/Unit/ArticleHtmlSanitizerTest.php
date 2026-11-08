<?php

namespace Tests\Unit;

use App\Services\ArticleHtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArticleHtmlSanitizerTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unsafeHtmlProvider(): array
    {
        return [
            'script element' => ['<p>a</p><script>alert(1)</script>', '<p>a</p>'],
            'event handler' => ['<p onclick="alert(1)">a</p>', '<p>a</p>'],
            'javascript link' => ['<a href="javascript:alert(1)">a</a>', '<a>a</a>'],
            'obfuscated javascript link' => ['<a href="  jav&#x09;ascript:alert(1)">a</a>', '<a>a</a>'],
            'data link' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">a</a>', '<a>a</a>'],
            'protocol relative image' => ['<img src="//evil.test/x.png">', ''],
            'svg data image' => ['<img src="data:image/svg+xml;base64,PHN2Zz4=">', ''],
            'iframe' => ['<iframe src="https://evil.test"></iframe><p>b</p>', '<p>b</p>'],
            'style with url' => ['<p style="background-color: url(https://evil.test); color: red">a</p>', '<p style="color: red">a</p>'],
            'disallowed style property' => ['<p style="position: fixed; text-align: right">a</p>', '<p style="text-align: right">a</p>'],
            'unknown element is unwrapped' => ['<section><p>a</p></section>', '<p>a</p>'],
            'comment' => ['<p>a<!-- <script>x</script> --></p>', '<p>a</p>'],
        ];
    }

    #[DataProvider('unsafeHtmlProvider')]
    public function test_unsafe_html_is_removed(string $input, string $expected): void
    {
        $this->assertSame($expected, (new ArticleHtmlSanitizer)->sanitize($input));
    }

    public function test_editor_formatting_and_vietnamese_text_are_kept(): void
    {
        $html = '<p style="text-align: center;"><b>Đậm</b> <i>nghiêng</i> <u>gạch</u></p>'
            .'<ul><li>Một</li></ul><table class="table table-bordered evil"><tbody><tr><td colspan="2">Ô</td></tr></tbody></table>'
            .'<p><a href="mailto:shop@aovu.vn">Email</a> <img src="data:image/png;base64,iVBORw0KGgo=" width="120"></p>';

        $this->assertSame(
            '<p style="text-align: center"><b>Đậm</b> <i>nghiêng</i> <u>gạch</u></p>'
            .'<ul><li>Một</li></ul><table class="table table-bordered"><tbody><tr><td colspan="2">Ô</td></tr></tbody></table>'
            .'<p><a href="mailto:shop@aovu.vn">Email</a> <img src="data:image/png;base64,iVBORw0KGgo=" width="120"></p>',
            (new ArticleHtmlSanitizer)->sanitize($html),
        );
    }

    public function test_plain_text_becomes_escaped_paragraphs(): void
    {
        $this->assertSame(
            "<p>Dòng 1<br>\nDòng &lt;2&gt;</p><p>Đoạn 2</p>",
            (new ArticleHtmlSanitizer)->sanitize("Dòng 1\nDòng <2>\n\n\nĐoạn 2"),
        );
    }

    public function test_inline_mode_keeps_only_inline_formatting(): void
    {
        $sanitizer = new ArticleHtmlSanitizer;
        $html = '<p><b>Đậm</b> <a href="https://x.test">link</a> <span style="color: red; float: left" onclick="x()">đỏ</span></p>'
            .'<p>dòng 2<img src="/a.png"></p><h2>dòng 3</h2>';

        $this->assertSame('<b>Đậm</b> link <span style="color: red">đỏ</span> dòng 2 dòng 3', $sanitizer->sanitizeInline($html));
        $this->assertSame('<b>Đậm</b> link <span style="color: red">đỏ</span><br>dòng 2<br>dòng 3', $sanitizer->sanitizeInline($html, multiline: true));
        $this->assertSame('', $sanitizer->sanitizeInline('<p><br></p>'));
    }

    public function test_inline_mode_escapes_plain_text(): void
    {
        $sanitizer = new ArticleHtmlSanitizer;

        $this->assertSame('Giá &lt;300k&gt; &amp; hơn', $sanitizer->sanitizeInline("Giá <300k>\n& hơn"));
        $this->assertSame("Dòng 1<br>\nDòng 2", $sanitizer->sanitizeInline("Dòng 1\n\nDòng 2", multiline: true));
    }

    public function test_plain_text_strips_formatting_and_decodes_entities(): void
    {
        $sanitizer = new ArticleHtmlSanitizer;

        $this->assertSame('SEO & thời trang dòng 2', $sanitizer->plainText('<p><b>SEO</b> &amp; thời&nbsp;trang</p><p>dòng 2</p>'));
        $this->assertSame('Giá <300k>', $sanitizer->plainText('Giá <300k>'));
    }

    public function test_blank_editor_content_is_detected(): void
    {
        $sanitizer = new ArticleHtmlSanitizer;

        $this->assertTrue($sanitizer->isBlank('<p><br></p><p>&nbsp;</p>'));
        $this->assertFalse($sanitizer->isBlank('<p><img src="/a.png"></p>'));
        $this->assertFalse($sanitizer->isBlank('<p>Chữ</p>'));
    }
}
