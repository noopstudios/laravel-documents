# Changelog

## 0.1.0 (por lançar)

- Cliente Gotenberg: `Pdf::view()`, `html()`, `office()` e `merge()`, com cabeçalho, rodapé, papel, orientação e margens.
- Autenticação básica opcional do Gotenberg (`GOTENBERG_USERNAME` e `GOTENBERG_PASSWORD`).
- Cliente word2pdf: `WordTemplate::fill()` para PDF/A ou docx e `fromHtml()` para docx.
- Normalização das variáveis dos modelos.
- `DocumentException` com o estado HTTP; repetição só em 503.
- `Pdf::fake()` e `WordTemplate::fake()` para testes na aplicação.
- Testes reais contra os serviços (`--group=real`).
