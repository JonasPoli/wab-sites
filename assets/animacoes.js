/*
 * Animações de entrada: substitui a biblioteca AOS lendo os mesmos atributos `data-aos`.
 *
 * O conteúdo NÃO nasce invisível. Esconder tudo em CSS deixava o topo da página em branco
 * até este script rodar — no banner isso dava 1,85 s de "atraso na renderização do
 * elemento" no PageSpeed. Aqui só é escondido o que está fora da tela, que o visitante
 * não vê mesmo; o que já está à vista aparece sem animação nenhuma.
 */
(function () {
    'use strict';

    var alvos = document.querySelectorAll('[data-aos]');
    if (!alvos.length) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || typeof IntersectionObserver === 'undefined') {
        return; // sem esconder nada: tudo já está visível
    }

    function revelar(elemento) {
        var atraso = parseInt(elemento.getAttribute('data-aos-delay') || '0', 10);
        var duracao = parseInt(elemento.getAttribute('data-aos-duration') || '0', 10);
        if (atraso) {
            elemento.style.setProperty('--aos-atraso', atraso + 'ms');
        }
        if (duracao) {
            elemento.style.setProperty('--aos-duracao', duracao + 'ms');
        }
        elemento.classList.add('aos-visivel');
        elemento.classList.remove('aos-oculto');
    }

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (!entrada.isIntersecting) {
                return;
            }
            revelar(entrada.target);
            observador.unobserve(entrada.target);
        });
    }, { rootMargin: '0px 0px -60px 0px', threshold: 0.05 });

    var altura = window.innerHeight;
    Array.prototype.forEach.call(alvos, function (elemento) {
        var caixa = elemento.getBoundingClientRect();
        if (caixa.top < altura && caixa.bottom > 0) {
            return; // já está à vista: fica como está, sem piscar
        }
        elemento.classList.add('aos-oculto');
        observador.observe(elemento);
    });
})();
