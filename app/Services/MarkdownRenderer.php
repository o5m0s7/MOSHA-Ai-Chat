<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;

class MarkdownRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);
    }

    public function render(string $markdown): string
    {
        // Normalize encoded spaces that some AI responses may contain
        $markdown = preg_replace(
            '/&#x?20;|&#32;/i',
            ' ',
            $markdown
        );
    
        $html = $this->converter
            ->convert($markdown)
            ->getContent();
    
        return $this->addCodeBlockFeatures($html);
    }

    private function addCodeBlockFeatures(string $html): string
    {
        return preg_replace_callback(
            '/<pre><code(?: class="language-([^"]+)")?>(.*?)<\/code><\/pre>/s',
            function ($matches) {

                $language = $matches[1] ?? '';
                $code = $matches[2];

                $languageLabel = $language !== ''
                    ? '<span class="code-language">' . e($language) . '</span>'
                    : '';

                return '
                    <div class="code-block">
                        ' . $languageLabel . '

                        <button
                            type="button"
                            class="copy-code-btn"
                        >
                            Copy
                        </button>

                        <pre><code class="' .
                            ($language !== '' ? 'language-' . e($language) : '') .
                        '">' . $code . '</code></pre>
                    </div>
                ';
            },
            $html
        );
    }
}
