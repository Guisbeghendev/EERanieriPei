import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Adiciona o token CSRF (Cross-Site Request Forgery) aos cabeçalhos de todas as
 * requisições Axios. Isso é crucial para a segurança de formulários e requisições
 * POST/PUT/DELETE no Laravel, prevenindo o erro 419 (Page Expired).
 *
 * O token é geralmente injetado no HTML via uma meta tag:
 * <meta name="csrf-token" content="{{ csrf_token() }}">
 */
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    // Isso é um aviso, pois o CSRF token é essencial para segurança.
    // Garanta que a meta tag '<meta name="csrf-token" content="{{ csrf_token() }}">'
    // esteja presente no cabeçalho do seu arquivo Blade principal (ex: app.blade.php).
    console.error('CSRF token não encontrado: Garanta que a meta tag "csrf-token" esteja presente no cabeçalho do seu HTML.');
}
