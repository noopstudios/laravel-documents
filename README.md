# laravel-documents

Cliente Laravel para o Gotenberg (PDFs) e para o word2pdf (modelos Word). Sem tabelas, jobs nem Filament. Requer PHP ^8.3, Laravel 13 e Gotenberg 9 (recomendado ≥ 9.4).

## Instalação

O repositório é privado e não está no Packagist. No `composer.json` da aplicação:

```json
"repositories": [
    { "type": "vcs", "url": "git@github.com:noopstudios/laravel-documents.git" }
]
```

```bash
composer require noopstudios/laravel-documents
```

Cada servidor e cada CI precisa de acesso de leitura ao repositório: uma deploy key SSH, ou um token GitHub só de leitura no `auth.json` do Composer (`composer config --global --auth github-oauth.github.com <token>`). Nunca o versionar.

## Configuração

| Variável | Descrição | Por omissão |
| --- | --- | --- |
| `GOTENBERG_URL` | Obrigatória para PDFs | nenhum |
| `GOTENBERG_USERNAME` | Opcional; autenticação básica do Gotenberg | nenhum |
| `GOTENBERG_PASSWORD` | Opcional; palavra-passe da autenticação básica do Gotenberg | nenhum |
| `GOTENBERG_TIMEOUT` | Segundos | 120 |
| `WORD2PDF_URL` | Obrigatória para modelos Word | nenhum |
| `WORD2PDF_API_KEY` | Opcional; enviada no cabeçalho `X-API-Key` | nenhum |
| `WORD2PDF_TIMEOUT` | Segundos | 60 |

```env
GOTENBERG_URL=https://gotenberg.example.test
WORD2PDF_URL=https://word2pdf.example.test
```

Sem o URL, o pacote lança `DocumentException` a dizer que variável falta. Para alterar o ficheiro de configuração: `php artisan vendor:publish --tag=documents-config`.

## Utilização

```php
use Noop\Documents\Facades\Pdf;
use Noop\Documents\Facades\WordTemplate;
use Noop\Documents\PdfA;

// PDF a partir de uma view (ou Pdf::html($html))
$pending = Pdf::view('invoices.show', ['invoice' => $invoice])
    ->header($headerHtml)
    ->footer($footerHtml)
    ->paper('A4')            // A3, A4, A5, Letter, Legal
    ->landscape()
    ->margins(30, 15, 30, 15); // topo, direita, baixo, esquerda, em mm

// em alternativa (cada uma converte o PDF):
$bytes = $pending->bytes();
return $pending->download('fatura.pdf');

$pdf = Pdf::view('invoices.show', ['invoice' => $invoice])->get(); // Document: bytes() e download()

// Word ou outro ficheiro Office para PDF, e junção de PDFs
$pdf = Pdf::office($docxBytes, 'anexo.docx');
$all = Pdf::merge([$pdfBytes, $pdf]);   // strings ou Document

// Modelo Word
WordTemplate::fill($path, $variables)->toPdf(PdfA::A2b);
WordTemplate::fill($path, $variables)->toDocx();

// HTML para Word (referência e imagens opcionais)
WordTemplate::fromHtml($html, $referencePath, ['logo.png' => $pngBytes])->toDocx();
```

`office()`, `merge()`, `toPdf()` e `toDocx()` devolvem um `Document` com `bytes()` e `download($filename)`. Em `download()`, `/` e `\` no nome do ficheiro são substituídos por `-`.

## Variáveis dos modelos

Os valores são normalizados antes de seguirem para o serviço:

- `null` e `false` passam a vazio; `true` passa a `1`.
- Listas e arrays aninhados são suportados (`['items' => [['title' => 'A']]]` chega como `items[0][title]`).
- Um array vazio passa a vazio.
- Chaves com `[` ou `]` são recusadas (`InvalidArgumentException`).
- Tipos não suportados (objetos sem `Stringable`) são recusados.

Uma armadilha que o pacote não resolve: uma variável usada no modelo e não passada imprime `undefined`. Passe sempre todas as variáveis.

## Erros

As falhas dos serviços (e a falta do URL) são `DocumentException`; `->status` tem o código HTTP, quando existe. Argumentos inválidos (formato de papel desconhecido, variável não suportada, ficheiro ilegível) lançam `InvalidArgumentException`, e `fromHtml()->toPdf()` lança `LogicException`. Só o HTTP 503 (serviço ocupado) é repetido, até 3 tentativas. Timeouts e outros erros não são repetidos. A resposta é verificada: tem de ser mesmo um PDF ou docx.

## Notas

- O Chromium corta o conteúdo do cabeçalho e rodapé que não cabe na margem; aumente `margins()`.
- O cabeçalho e o rodapé têm de ser documentos HTML completos.
- `Pdf::merge()` mantém a ordem recebida.
- `WordTemplate::...->toPdf(PdfA::...)` exige que o word2pdf use o Gotenberg 9; caso contrário o PDF volta sem PDF/A e sem erro.
- Um docx sem tamanho de página sai em Letter (`Pdf::office()`); os modelos criados no Word trazem o tamanho.
- Em `fromHtml()`, o HTML tem de ser UTF-8 e as imagens são referenciadas pelo nome do ficheiro (máximo 20).
- `fromHtml()` não tem `toPdf()`; use `Pdf::html()` para um PDF.

## Testes na aplicação

```php
Pdf::fake();
WordTemplate::fake();

Pdf::view('invoices.show', ['invoice' => $invoice])->bytes();

Pdf::assertGenerated('view', fn (array $call) => $call['view'] === 'invoices.show');
WordTemplate::assertNothingGenerated();
```

Os fakes não fazem chamadas HTTP nem leem views ou ficheiros. Cada chamada registada tem as chaves: `view`: `view`, `data`; `html`: `html`; `office`: `filename`; `merge`: `count`; `fill`: `path`, `variables`; `fromHtml`: `html`, `reference`, `images`. Tipos registados: `view`, `html`, `office`, `merge` (`Pdf`) e `fill`, `fromHtml` (`WordTemplate`).

## Testes do pacote

```bash
vendor/bin/pest
```

Os testes reais chamam os serviços verdadeiros, com as três variáveis exportadas na shell. Nunca em CI.

```bash
GOTENBERG_URL=... WORD2PDF_URL=... WORD2PDF_API_KEY=... vendor/bin/pest --group=real
```
