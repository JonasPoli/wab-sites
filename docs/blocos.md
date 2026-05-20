Crie novos módulos.
Nestes novos módulos, precisamoas que sejam criados pensando que devem ser para os temas claros e escutos para os 3 temas já existentes:  cetec, moderno e wab.

# blocos de membros da equipe
Pré-título
Título
Texto de apoio

e deve permitir inserir vários membros na equipe, onde cada membro pode ter
imagem
nome
cargo
minicurriculo
url do linkedim
url do facebook
url do instagram
url do whatsapp
fone de contato
e-mail
Os campos devem ser todos opcionais.

Na hora de mostrar os blocos, o sistema deve mostrar de 3 em 3, ou seja, 3 em cada linha, todos com a mesma largura, distãncia, etc.
Porém, caso um card ou 2, sobre para o final e fique ocupando uma linha de um unico card, este deve ter a mesma largura que os demais, acima, e aparecer centralizado.

No painel administrativo deve ser possível reposicionar a ordem dos membros.

# bloco contato
deve ser construído com o formulário e os itens de contato.
os dados de contato que serão exbidos na área pública são os que foram enviados em 
/superadmin/tenant/1/edit
Os campos serão fixos.
o admnistrador só informa, do bloco, os dados:
Pré-título
Título
Texto de apoio

O visual deve ser o seguinte:

```html
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

                        <div data-aos="fade-right" class="aos-init aos-animate">
                <h2 class="font-headline text-2xl font-bold text-gray-900 mb-2">Envie uma Mensagem</h2>
                <p class="text-gray-500 text-sm mb-8">Respondemos em até 24 horas úteis.</p>

                <form name="contato_publico" method="post" class="space-y-5" novalidate="novalidate">
                    <div>
                                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1 required" for="contato_publico_nome">
            Nome Completo *
        </label>
    
                                <input type="text" id="contato_publico_nome" name="contato_publico[nome]" required="required" class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-cetec-orange transition-colors bg-white block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" placeholder="Seu nome">

                            
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                                                                                                        <label class="block text-sm font-semibold text-gray-700 mb-1 required" for="contato_publico_email">
            E-mail *
        </label>
    
                                    <input type="email" id="contato_publico_email" name="contato_publico[email]" required="required" class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-cetec-orange transition-colors bg-white block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" placeholder="seu@email.com">

                                
                        </div>
                        <div>
                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="contato_publico_telefone">
            Telefone / WhatsApp
        </label>
    
                                    <input type="tel" id="contato_publico_telefone" name="contato_publico[telefone]" class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-cetec-orange transition-colors bg-white block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" placeholder="(16) 99999-9999">

                                
                        </div>
                    </div>
                    <div>
                                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1 required" for="contato_publico_mensagem">
            Mensagem *
        </label>
    
                                <textarea id="contato_publico_mensagem" name="contato_publico[mensagem]" required="required" class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-cetec-orange transition-colors bg-white block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" rows="5" placeholder="Como podemos ajudar?"></textarea>

                            
                    </div>
                    <button type="submit" class="btn-cetec-primary w-full justify-center py-4 text-base">
                        <i class="bi bi-send"></i> Enviar Mensagem
                    </button>
                        <input type="hidden" id="contato_publico__token" name="contato_publico[_token]" data-controller="csrf-protection" class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" value="csrf-token">
</form>
            </div>

                        <div data-aos="fade-left" class="aos-init aos-animate">
                <h2 class="font-headline text-2xl font-bold text-gray-900 mb-2">Nosso Endereço</h2>
                <p class="text-gray-500 text-sm mb-8">Venha nos visitar presencialmente ou entre em contato pelos canais abaixo.</p>

                <div class="space-y-5 mb-8">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:rgba(255,127,0,0.1)">
                            <i class="bi bi-geo-alt-fill text-xl" style="color:#ff7f00"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Endereço</p>
                            <p class="text-gray-500 text-sm">Av. Brasil, 782 - Centro<br>Araraquara - SP, 14801-050</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:rgba(255,127,0,0.1)">
                            <i class="bi bi-telephone-fill text-xl" style="color:#ff7f00"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Telefone</p>
                            <a href="tel:+551633362414" class="text-gray-500 text-sm hover:text-cetec-orange transition-colors text-decoration-none">(16) 3336-2414</a>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:rgba(255,127,0,0.1)">
                            <i class="bi bi-envelope-fill text-xl" style="color:#ff7f00"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">E-mail</p>
                            <a href="mailto:contato@cetecararaquara.com.br" class="text-gray-500 text-sm hover:text-cetec-orange transition-colors text-decoration-none">
                                contato@cetecararaquara.com.br
                            </a>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:rgba(37,211,102,0.1)">
                            <i class="bi bi-whatsapp text-xl" style="color:#25d366"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">WhatsApp</p>
                            <a href="https://wa.me/551633362414" target="_blank" class="text-sm font-semibold text-decoration-none hover:underline" style="color:#25d366">
                                Clique para conversar
                            </a>
                        </div>
                    </div>
                </div>

                                

                                <div class="mt-6 p-4 bg-white border border-gray-200 rounded">
                    <h4 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-3">Horário de Funcionamento</h4>
                    <div class="space-y-1 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span>Segunda a Sexta:</span>
                            <span class="font-semibold">08h00 às 22h00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Sábado:</span>
                            <span class="font-semibold">08h00 às 13h00</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
```