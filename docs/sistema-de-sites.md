Crie um novo documento chamado wab-sites.md que descreva todas as mudanças que este sistema precisará possuir para funcionar como um sistema de geração de sites da WAB.

Lembre-se que seus tokens são limitados então pense sempre em tarefas menores. Divida a execução desse trabalho em partes menores, mas não pule nada.

documente que todos os ajustes no banco devesão ser feitos via migrations.

Analise tudo antes e estude a melhor forma de implementar este sistema de gerenciador de sites.
Pense em tudo que poderia ter para melhorar o site.

Faça todos os ajustes solictados abaixo, não se limitando a estes.

Faça o plano mais completo possivel, como se fosse ser executado por outra IA.

# Mudar sistema
Vamos criar um plano para ajustar este projeto para ser um sistema de geração de sites da WAB.
Remova qualquer mensão à NEPE e troque para WAB.

Vamos ajustar o sistma de sessões e blocos.
Os blocos, atualmente, só podem ser de "imagem e texto"
Quero que o botão "Novo Bloco" seja possível adicionar o atual bloco de texto de um lado e imagem de outros, além de novos tipos de blocos, como:

## Bloco de galeria
Galeria de imagens, que permite upar várias imagens com suas legendas, além de pré-titulo, título, texto com descrição completa tinemce.

## Bloco de NewsLetter
 pré-titulo, título, texto com descrição completa
Blorb1: icone, titulo, texto
Blorb2: icone, titulo, texto
```
<div class="relative isolate overflow-hidden bg-gray-900 py-16 sm:py-24 lg:py-32">
  <div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-2">
      <div class="max-w-xl lg:max-w-lg">
        <h2 class="text-4xl font-semibold tracking-tight text-white">Subscribe to our newsletter</h2>
        <p class="mt-4 text-lg text-gray-300">Nostrud amet eu ullamco nisi aute in ad minim nostrud adipisicing velit quis. Duis tempor incididunt dolore.</p>
        <div class="mt-6 flex max-w-md gap-x-4">
          <label for="email-address" class="sr-only">Email address</label>
          <input id="email-address" type="email" name="email" required placeholder="Enter your email" autocomplete="email" class="min-w-0 flex-auto rounded-md bg-white/5 px-3.5 py-2 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
          <button type="submit" class="flex-none rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Subscribe</button>
        </div>
      </div>
      <dl class="grid grid-cols-1 gap-x-8 gap-y-10 sm:grid-cols-2 lg:pt-2">
        <div class="flex flex-col items-start">
          <div class="rounded-md bg-white/5 p-2 ring-1 ring-white/10">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
              <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
          <dt class="mt-4 text-base font-semibold text-white">Weekly articles</dt>
          <dd class="mt-2 text-base/7 text-gray-400">Non laboris consequat cupidatat laborum magna. Eiusmod non irure cupidatat duis commodo amet.</dd>
        </div>
        <div class="flex flex-col items-start">
          <div class="rounded-md bg-white/5 p-2 ring-1 ring-white/10">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
              <path d="M10.05 4.575a1.575 1.575 0 1 0-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 0 1 3.15 0v1.5m-3.15 0 .075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 0 1 3.15 0V15M6.9 7.575a1.575 1.575 0 1 0-3.15 0v8.175a6.75 6.75 0 0 0 6.75 6.75h2.018a5.25 5.25 0 0 0 3.712-1.538l1.732-1.732a5.25 5.25 0 0 0 1.538-3.712l.003-2.024a.668.668 0 0 1 .198-.471 1.575 1.575 0 1 0-2.228-2.228 3.818 3.818 0 0 0-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0 1 16.35 15m.002 0h-.002" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
          <dt class="mt-4 text-base font-semibold text-white">No spam</dt>
          <dd class="mt-2 text-base/7 text-gray-400">Officia excepteur ullamco ut sint duis proident non adipisicing. Voluptate incididunt anim.</dd>
        </div>
      </dl>
    </div>
  </div>
  <div aria-hidden="true" class="absolute top-0 left-1/2 -z-10 -translate-x-1/2 blur-3xl xl:-top-6">
    <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="aspect-1155/678 w-288.75 bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30"></div>
  </div>
</div>
```

o sistema de ícones deve ser o apresentado no projeto neste mesmo computador:
/Users/jonaspoli/work/html/colegio-conexao-2026
no 
https://conexao.wab.com.br/admin/extracurricular/1/edit
Ícone Font Awesome
que pode ser escolhido ou enviado uma imagem.
Quando o usuário navegando no site por este módulo informar seu e-mail, o sistema deve gravar o e-mail e enviar uma mensagem dizendo que os dados foram salvos,

Os e-mails cadastrados devem aparecer num relatório no admin de exportar .csv



## Fale conosco

Deve apresentar publicamente um módulo similar a este.
No admin, o administrador deve porder cadastrar quais campos quer, além de nome, endereço, telefone e-mail e contato que deve ser padrão.

Na área púbica, quando o visitante enviar um e-mail, o sistema deve mostrar uma mensagem de sucesso, e enviar um e-mail para o administrador.
O e-mail deve ir para um relatório do administrador.

```html
<section class="py-20 bg-white">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.5fr] gap-12 items-start">

                        <div class="space-y-8 aos-init aos-animate" data-aos="fade-right">
                <div>
                    <h2 class="text-2xl font-black text-primary uppercase mb-6">Atendimento</h2>
                    <div class="flex items-center gap-3 text-slate-600 font-bold mb-2">
                        <span class="material-symbols-outlined text-secondary">schedule</span>
                        De Seg a Sex — 7h00 às 18h00
                    </div>
                    <div class="flex items-center gap-3 text-slate-600 font-bold">
                        <span class="material-symbols-outlined text-secondary">schedule</span>
                        Sábados — 8h00 às 12h00
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-secondary">location_on</span>
                        </div>
                        <div>
                            <strong class="block text-primary uppercase text-sm mb-1">Unidade I — Ensino Médio | Pré-Vestibular</strong>
                            <p class="text-slate-500 text-sm">Av. Dom Pedro II, nº 60 — Centro — Araraquara/SP</p>
                            <p class="text-primary font-bold mt-1">(16) 3301.5800</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-secondary">location_on</span>
                        </div>
                        <div>
                            <strong class="block text-primary uppercase text-sm mb-1">Unidade II — Ensino Fundamental II</strong>
                            <p class="text-slate-500 text-sm">Rua Major Carvalho Filho, nº 980 — Centro — Araraquara/SP</p>
                            <p class="text-primary font-bold mt-1">(16) 3303.9060</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100">
                    <h4 class="text-primary font-black uppercase mb-4">Siga-nos</h4>
                    <div class="flex gap-4">
                        <a href="https://www.instagram.com/conexaoetapa/" target="_blank" class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center text-primary hover:bg-secondary hover:text-white transition-all shadow-md">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="https://www.facebook.com/colegioecursoconexao" target="_blank" class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center text-primary hover:bg-secondary hover:text-white transition-all shadow-md">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>

                        <div data-aos="fade-left" class="aos-init aos-animate">

                                <div id="tab-fale" class="tab-panel">
                    <div class="rounded-[32px] bg-slate-50 p-8 lg:p-10 shadow-xl border border-slate-200">
                        <h3 class="text-2xl font-black text-primary uppercase mb-6">
                            <i class="fas fa-envelope text-secondary mr-2"></i>Fale Conosco
                        </h3>
                        <form action="/contato?tab=fale" method="post" class="space-y-5">
                            <input type="hidden" name="_tab" value="fale">
                            <div>
                                <label for="name-fale" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">Nome Completo *</label>
                                <input id="name-fale" name="name" type="text" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10" placeholder="Como podemos te chamar?">
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="email-fale" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">E-mail *</label>
                                    <input id="email-fale" name="email" type="email" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10" placeholder="seu@email.com">
                                </div>
                                <div>
                                    <label for="phone-fale" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">WhatsApp</label>
                                    <input id="phone-fale" name="phone" type="tel" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            <div>
                                <label for="message-fale" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">Mensagem *</label>
                                <textarea id="message-fale" name="message" rows="5" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10" placeholder="Conte-nos como podemos ajudar..."></textarea>
                            </div>
                            <button type="submit" class="w-full rounded-full bg-secondary px-12 py-5 text-sm font-black text-white shadow-xl transition hover:brightness-110 uppercase tracking-widest flex items-center justify-center gap-2">
                                Enviar Mensagem <span class="material-symbols-outlined text-sm">send</span>
                            </button>
                        </form>
                    </div>
                </div>

                                <div id="tab-visita" class="tab-panel hidden">
                    <div class="rounded-[32px] bg-slate-50 p-8 lg:p-10 shadow-xl border border-slate-200">
                        <h3 class="text-2xl font-black text-primary uppercase mb-6">
                            <i class="fas fa-calendar-check text-secondary mr-2"></i>Agende uma Visita
                        </h3>
                        <p class="text-slate-500 mb-6">Venha conhecer nossa estrutura, os professores e a proposta pedagógica do Colégio Conexão pessoalmente.</p>
                        <form action="/agende-uma-visita" method="post" class="space-y-5">
                            <div>
                                <label for="name-visita" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">Nome Completo *</label>
                                <input id="name-visita" name="name" type="text" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10">
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="email-visita" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">E-mail *</label>
                                    <input id="email-visita" name="email" type="email" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10">
                                </div>
                                <div>
                                    <label for="phone-visita" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">WhatsApp *</label>
                                    <input id="phone-visita" name="phone" type="tel" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary focus:ring-4 focus:ring-secondary/10" placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            <div>
                                <label for="segmento-visita" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">Segmento de Interesse *</label>
                                <select id="segmento-visita" name="message" required="" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary">
                                    <option value="">Selecione...</option>
                                    <option value="Fundamental Anos Iniciais (1º ao 5º Ano)">Fundamental Anos Iniciais (1º ao 5º Ano)</option>
                                    <option value="Fundamental Anos Finais (6º ao 9º Ano)">Fundamental Anos Finais (6º ao 9º Ano)</option>
                                    <option value="Ensino Médio (1º ao 3º Ano)">Ensino Médio (1º ao 3º Ano)</option>
                                    <option value="Extensivo / Pré-Vestibular">Extensivo / Pré-Vestibular</option>
                                    <option value="ConectMed">ConectMed</option>
                                </select>
                            </div>
                            <div>
                                <label for="date-visita" class="block text-xs font-black text-primary uppercase tracking-widest mb-2">Data Preferida</label>
                                <input id="date-visita" name="visitDate" type="date" class="w-full rounded-xl border-2 border-slate-300 bg-white px-5 py-4 text-primary outline-none transition focus:border-secondary">
                            </div>
                            <button type="submit" class="w-full rounded-full bg-secondary px-12 py-5 text-sm font-black text-white shadow-xl transition hover:brightness-110 uppercase tracking-widest flex items-center justify-center gap-2">
                                Solicitar Visita <i class="fas fa-calendar-check ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>

                                <div id="tab-inscricao" class="tab-panel hidden">
                    <div class="rounded-[32px] bg-slate-50 p-8 lg:p-10 shadow-xl border border-slate-200">
                        <h3 class="text-2xl font-black text-primary uppercase mb-4">
                            <i class="fas fa-user-plus text-secondary mr-2"></i>Pré-inscrição
                        </h3>
                        <p class="text-slate-500 mb-8">Selecione o segmento de interesse para acessar o formulário específico de pré-inscrição.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <a href="/pre-inscricao/fundamental-anos-iniciais" class="group flex items-center gap-4 p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-[#0dbbef] transition-all duration-300 hover:shadow-lg">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white font-black text-sm transition-all duration-300" style="background-color: #0dbbef;">1-5</div>
                                <div>
                                    <p class="font-black text-primary text-sm uppercase tracking-wide">Fund. Anos Iniciais</p>
                                    <p class="text-slate-400 text-xs">1º ao 5º Ano</p>
                                </div>
                                <i class="fas fa-arrow-right ml-auto text-slate-300 group-hover:text-[#0dbbef] transition-colors"></i>
                            </a>
                            <a href="/pre-inscricao/fundamental-anos-finais" class="group flex items-center gap-4 p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-[#007bc2] transition-all duration-300 hover:shadow-lg">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white font-black text-sm transition-all duration-300" style="background-color: #007bc2;">6-9</div>
                                <div>
                                    <p class="font-black text-primary text-sm uppercase tracking-wide">Fund. Anos Finais</p>
                                    <p class="text-slate-400 text-xs">6º ao 9º Ano</p>
                                </div>
                                <i class="fas fa-arrow-right ml-auto text-slate-300 group-hover:text-[#007bc2] transition-colors"></i>
                            </a>
                            <a href="/pre-inscricao/ensino-medio" class="group flex items-center gap-4 p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-[#133880] transition-all duration-300 hover:shadow-lg">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white font-black text-xs transition-all duration-300" style="background-color: #133880;">EM</div>
                                <div>
                                    <p class="font-black text-primary text-sm uppercase tracking-wide">Ensino Médio</p>
                                    <p class="text-slate-400 text-xs">1º ao 3º Ano</p>
                                </div>
                                <i class="fas fa-arrow-right ml-auto text-slate-300 group-hover:text-[#133880] transition-colors"></i>
                            </a>
                            <a href="/pre-inscricao/pre-vestibular" class="group flex items-center gap-4 p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-[#0b1742] transition-all duration-300 hover:shadow-lg">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white font-black text-xs transition-all duration-300" style="background-color: #0b1742;">EXT</div>
                                <div>
                                    <p class="font-black text-primary text-sm uppercase tracking-wide">Extensivo</p>
                                    <p class="text-slate-400 text-xs">Pré-Vestibular</p>
                                </div>
                                <i class="fas fa-arrow-right ml-auto text-slate-300 group-hover:text-[#0b1742] transition-colors"></i>
                            </a>
                            <a href="/pre-inscricao/pre-vestibular" class="group flex items-center gap-4 p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-[#38b6ab] transition-all duration-300 hover:shadow-lg sm:col-span-2">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white font-black text-xs transition-all duration-300" style="background-color: #38b6ab;">MED</div>
                                <div>
                                    <p class="font-black text-primary text-sm uppercase tracking-wide">ConectMed</p>
                                    <p class="text-slate-400 text-xs">Medicina Integral</p>
                                </div>
                                <i class="fas fa-arrow-right ml-auto text-slate-300 group-hover:text-[#38b6ab] transition-colors"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
```


## Banners
A sessão de banners 

Cada banner cadastrado nesta sessão deve ter os seguintes campos:

magem de fundo
Título *
Subtítulo
Texto do botão (CTA)
Link do botão
ativo

Neste computador, veja como funciona na área pública o projeto ~/work/html/site-de-nepe
Faça o mesmo sistema, com slides, com bolinhas e com setinhas laterais para navegar.


## Texto com 4 blurbs

 pré-titulo, título, texto com descrição completa
Blorb1: icone, titulo, texto
Blorb2: icone, titulo, texto
Blorb3: icone, titulo, texto
Blorb4: icone, titulo, texto



```html
<div class="bg-white py-24 sm:py-32">
  <div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="mx-auto max-w-2xl lg:text-center">
      <h2 class="text-base/7 font-semibold text-indigo-600">Deploy faster</h2>
      <p class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl lg:text-balance">Everything you need to deploy your app</p>
      <p class="mt-6 text-lg/8 text-gray-700">Quis tellus eget adipiscing convallis sit sit eget aliquet quis. Suspendisse eget egestas a elementum pulvinar et feugiat blandit at. In mi viverra elit nunc.</p>
    </div>
    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
      <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
        <div class="relative pl-16">
          <dt class="text-base/7 font-semibold text-gray-900">
            <div class="absolute top-0 left-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
                <path d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            Push to deploy
          </dt>
          <dd class="mt-2 text-base/7 text-gray-600">Morbi viverra dui mi arcu sed. Tellus semper adipiscing suspendisse semper morbi. Odio urna massa nunc massa.</dd>
        </div>
        <div class="relative pl-16">
          <dt class="text-base/7 font-semibold text-gray-900">
            <div class="absolute top-0 left-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
                <path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            SSL certificates
          </dt>
          <dd class="mt-2 text-base/7 text-gray-600">Sit quis amet rutrum tellus ullamcorper ultricies libero dolor eget. Sem sodales gravida quam turpis enim lacus amet.</dd>
        </div>
        <div class="relative pl-16">
          <dt class="text-base/7 font-semibold text-gray-900">
            <div class="absolute top-0 left-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
                <path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            Simple queues
          </dt>
          <dd class="mt-2 text-base/7 text-gray-600">Quisque est vel vulputate cursus. Risus proin diam nunc commodo. Lobortis auctor congue commodo diam neque.</dd>
        </div>
        <div class="relative pl-16">
          <dt class="text-base/7 font-semibold text-gray-900">
            <div class="absolute top-0 left-0 flex size-10 items-center justify-center rounded-lg bg-indigo-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
                <path d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            Advanced security
          </dt>
          <dd class="mt-2 text-base/7 text-gray-600">Arcu egestas dolor vel iaculis in ipsum mauris. Tincidunt mattis aliquet hac quis. Id hac maecenas ac donec pharetra eget.</dd>
        </div>
      </dl>
    </div>
  </div>
</div>
```

o sistema de ícones deve ser o apresentado no projeto neste mesmo computador:
/Users/jonaspoli/work/html/colegio-conexao-2026
no 
https://conexao.wab.com.br/admin/extracurricular/1/edit
Ícone Font Awesome
que pode ser escolhido ou enviado uma imagem.
Quando o usuário navegando no site por este módulo informar seu e-mail, o sistema deve gravar o e-mail e enviar uma mensagem dizendo que os dados foram salvos,


## Stats

Pré-titulo, título, texto com descrição completa
Stat1: número, título, texto
Stat2: número, título, texto
Stat3: número, título, texto
Stat4: número, título, texto


Exemplo:

```html
<div class="bg-white py-24 sm:py-32">
  <div class="mx-auto max-w-7xl px-6 lg:px-8">
    <dl class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-3">
      <div class="mx-auto flex max-w-xs flex-col gap-y-4">
        <dt class="text-base/7 text-gray-600">Transactions every 24 hours</dt>
        <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 sm:text-5xl">44 million</dd>
      </div>
      <div class="mx-auto flex max-w-xs flex-col gap-y-4">
        <dt class="text-base/7 text-gray-600">Assets under holding</dt>
        <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 sm:text-5xl">$119 trillion</dd>
      </div>
      <div class="mx-auto flex max-w-xs flex-col gap-y-4">
        <dt class="text-base/7 text-gray-600">New users annually</dt>
        <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 sm:text-5xl">46,000</dd>
      </div>
    </dl>
  </div>
</div>

```


## Bloco chamada para notícia
Este vai ser para quando estiver construindo algo como a home.
O que o administrador deve escolher é
Quantidade de itens.


## Mapa
O administrador informa a url do mapa a ser incorporado:
https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d650.6573610228775!2d-37.060987954727395!3d-10.91801014227466!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x71ab3755015ad41%3A0x92472b41261a2f21!2sCentro%20Esp%C3%ADrita%20Amor%20e%20Caridade!5e0!3m2!1spt-BR!2sbr!4v1778691474467!5m2!1spt-BR!2sbr

e na área pública, aparece o iframe do mapa


## Listar SubCategorias
Pré-titulo, título, texto com descrição completa
O administrador deve escolher qual categoria quer ver a subcategorias.

silimar a 
```html
<section class="bg-white dark:bg-gray-900">
  <div class="py-8 px-4 mx-auto max-w-screen-xl sm:py-16 lg:px-6">
      <div class="max-w-screen-md mb-8 lg:mb-16">
          <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white">Designed for business teams like yours</h2>
          <p class="text-gray-500 sm:text-xl dark:text-gray-400">Here at Flowbite we focus on markets where technology, innovation, and capital can unlock long-term value and drive economic growth.</p>
      </div>
      <div class="space-y-8 md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-12 md:space-y-0">
          <div>
              <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-primary-100 lg:h-12 lg:w-12 dark:bg-primary-900">
                  <svg class="w-5 h-5 text-primary-600 lg:w-6 lg:h-6 dark:text-primary-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
              </div>
              <h3 class="mb-2 text-xl font-bold dark:text-white">Marketing</h3>
              <p class="text-gray-500 dark:text-gray-400">Plan it, create it, launch it. Collaborate seamlessly with all  the organization and hit your marketing goals every month with our marketing plan.</p>
          </div>
          <div>
              <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-primary-100 lg:h-12 lg:w-12 dark:bg-primary-900">
                  <svg class="w-5 h-5 text-primary-600 lg:w-6 lg:h-6 dark:text-primary-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path></svg>
              </div>
              <h3 class="mb-2 text-xl font-bold dark:text-white">Legal</h3>
              <p class="text-gray-500 dark:text-gray-400">Protect your organization, devices and stay compliant with our structured workflows and custom permissions made for you.</p>
          </div>
          <div>
              <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-primary-100 lg:h-12 lg:w-12 dark:bg-primary-900">
                  <svg class="w-5 h-5 text-primary-600 lg:w-6 lg:h-6 dark:text-primary-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"></path></svg>                    
              </div>
              <h3 class="mb-2 text-xl font-bold dark:text-white">Business Automation</h3>
              <p class="text-gray-500 dark:text-gray-400">Auto-assign tasks, send Slack messages, and much more. Now power up with hundreds of new templates to help you get started.</p>
          </div>
          <div>
              <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-primary-100 lg:h-12 lg:w-12 dark:bg-primary-900">
                  <svg class="w-5 h-5 text-primary-600 lg:w-6 lg:h-6 dark:text-primary-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>
              </div>
              <h3 class="mb-2 text-xl font-bold dark:text-white">Finance</h3>
              <p class="text-gray-500 dark:text-gray-400">Audit-proof software built for critical financial operations like month-end close and quarterly budgeting.</p>
          </div>
          <div>
              <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-primary-100 lg:h-12 lg:w-12 dark:bg-primary-900">
                  <svg class="w-5 h-5 text-primary-600 lg:w-6 lg:h-6 dark:text-primary-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path></svg>
              </div>
              <h3 class="mb-2 text-xl font-bold dark:text-white">Enterprise Design</h3>
              <p class="text-gray-500 dark:text-gray-400">Craft beautiful, delightful experiences for both marketing and product with real cross-company collaboration.</p>
          </div>
          <div>
              <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-primary-100 lg:h-12 lg:w-12 dark:bg-primary-900">
                  <svg class="w-5 h-5 text-primary-600 lg:w-6 lg:h-6 dark:text-primary-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>
              </div>
              <h3 class="mb-2 text-xl font-bold dark:text-white">Operations</h3>
              <p class="text-gray-500 dark:text-gray-400">Keep your company’s lights on with customizable, iterative, and structured workflows built for all efficient teams and individual.</p>
          </div>
      </div>
  </div>
</section>
```

Cada card deve exibir o icone, título, texto e descrição de cada uma das subcategorias da categria escolhida.





## Listar Páginas
Pré-titulo, título, texto com descrição completa
O administrador deve escolher qual categoria ou subcategoria quer ver as páginas.
Na área pública, deve listar o titulo e parte da descrição de cada página com o botão ler mais.
Caso a página possua uma imagem, deve exibir a imagem no card.
Ao clicar em ler mais, deve abrir a página.

A home deve ser uma página construída dessa maneira, como uma página.

# Novas funcionalidades
As páginas, blocos e sessões devem ter a opção de duplicar. O duplicar duplica todos os textos, sessões, partes e as imagens.
As páginas devem possuir a ordem gerenciada por arrastar e soltar na listagem
A página, /admin/page/1/edit, deve ter uma imagem principal.

# Categorias e subcategorias
Hoje, o sistema tem Estudos e Vídeos.
Estes conteúdos devem deixar de existir como estão.
Ao inves disso, o sistema deve ter um sistema de gerenciar categorias
como em ~/work/html/site-de-nepe/ no projeto neste computador /admin/category
As páginas criadas devem ser possível escolher de qual categorias ou subcategoria a página pertence.

Cada categoria e cada subcategoria deve ter um ícone
o sistema de ícones deve ser o apresentado no projeto neste mesmo computador:
/Users/jonaspoli/work/html/colegio-conexao-2026
no 
https://conexao.wab.com.br/admin/extracurricular/1/edit
Ícone Font Awesome

# Administração geral
em
admin/settings

deve permitir informar qual será a página que será a home
não deve ter mais o "Quem Somos — Home" nem o "Imagem do "Quem Somos" nessa área.

Adicione o gerenciiamento de favicon e de mais técnicas de SEO necessárias.

# Aprovação
Remova completamente o sistema de aprovaçao e de comunicação interna entre os admins.


