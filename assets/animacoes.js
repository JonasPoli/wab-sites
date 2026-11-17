/*
 * Animações de entrada: substitui a biblioteca AOS lendo os mesmos atributos `data-aos`.
 *
 * Um IntersectionObserver só; nada é baixado de fora do domínio. Ver assets/styles/animacoes.css.
 */
(function () {
    'use strict';

    var alvos = document.querySelectorAll('[data-aos]');
    if (!alvos.length) {
        return;
    }

    var menosMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function mostrar(elemento) {
        var atraso = parseInt(elemento.getAttribute('data-aos-delay') || '0', 10);
        var duracao = parseInt(elemento.getAttribute('data-aos-duration') || '0', 10);
        if (atraso) {
            elemento.style.setProperty('--aos-atraso', atraso + 'ms');
        }
        if (duracao) {
            elemento.style.setProperty('--aos-duracao', duracao + 'ms');
        }
        elemento.classList.add('aos-visivel');
    }

    // Sem suporte ao observador, ou com movimento reduzido, o conteúdo aparece direto
    if (menosMovimento || typeof IntersectionObserver === 'undefined') {
        Array.prototype.forEach.call(alvos, mostrar);
        return;
    }

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (!entrada.isIntersecting) {
                return;
            }
            mostrar(entrada.target);
            observador.unobserve(entrada.target);
        });
    }, { rootMargin: '0px 0px -60px 0px', threshold: 0.05 });

    Array.prototype.forEach.call(alvos, function (elemento) {
        // O que já está visível na abertura aparece sem esperar rolagem
        var caixa = elemento.getBoundingClientRect();
        if (caixa.top < window.innerHeight && caixa.bottom > 0) {
            mostrar(elemento);
            return;
        }
        observador.observe(elemento);
    });
})();
