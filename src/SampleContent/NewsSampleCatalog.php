<?php

namespace App\SampleContent;

/**
 * Textos fictícios do conteúdo de exemplo do tema noticias (app:conteudo-exemplo).
 *
 * Tudo aqui é inventado: pessoas, lugares e entidades não existem. As notícias
 * são relatos neutros (nada de inscrição aberta, promoção ou convite), porque
 * ficam no ar no domínio real do cliente enquanto ele vê o layout.
 */
final class NewsSampleCatalog
{
    /** Editorias: slug => [nome, descrição, slug da editoria pai ou null] */
    public const CATEGORIES = [
        'institucional' => ['Institucional', 'Atividades da associação e dos associados.', null],
        'cidades'       => ['Cidades', 'O dia a dia dos municípios da região.', null],
        'capital'       => ['Capital', 'O que acontece na capital.', 'cidades'],
        'cultura'       => ['Cultura', 'Música, literatura, cinema e exposições.', null],
        'formacao'      => ['Formação', 'Cursos, oficinas e debates sobre o jornalismo.', null],
    ];

    /** Jornalistas: [nome, veículo, cidade, minibio, com foto] */
    public const AUTHORS = [
        ['Lívia Andrade', 'Rádio Comunitária (exemplo)', 'Capital', 'Repórter de cidades há doze anos. Gosta de histórias de bairro, de feiras e de gente que faz a cidade funcionar.', true],
        ['Tomás Ribeiro', 'Revista Regional (exemplo)', 'Interior', 'Editor de cultura e fotógrafo. Cobre festivais, livros e a cena artística do interior.', true],
        ['Clara Nogueira', 'Jornal do Bairro (exemplo)', 'Capital', 'Jornalista e professora. Escreve sobre formação profissional e ética na comunicação.', false],
    ];

    /**
     * Notícias: t = título, k = chapéu, s = linha fina, c = editoria principal,
     * p = parágrafos, h = intertítulo, l = itens da lista, q = [citação, quem disse].
     *
     * @return list<array{t: string, k: string, s: string, c: string, p: list<string>, h: string, l: list<string>, q: array{string, string}}>
     */
    public static function articles(): array
    {
        return [
            // ---- Institucional ----------------------------------------------
            [
                't' => 'Encontro regional reúne associados para debater o futuro da profissão',
                'k' => 'Encontro',
                's' => 'Jornalistas de várias cidades passaram o sábado discutindo novas formas de financiar o jornalismo local e de aproximar as redações dos leitores.',
                'c' => 'institucional',
                'p' => [
                    'O encontro começou cedo, com uma roda de apresentação em que cada participante contou como é a rotina da sua redação. Muitos relataram equipes pequenas, que acumulam as funções de repórter, fotógrafo e editor.',
                    'À tarde, os grupos de trabalho trataram de três temas: sustentabilidade financeira, relação com o público e saúde dos profissionais. As propostas serão reunidas num documento e discutidas nos próximos encontros.',
                ],
                'h' => 'O que saiu dos grupos de trabalho',
                'l' => ['Troca de conteúdo entre veículos pequenos da região', 'Encontros periódicos com leitores nas redações', 'Rede de apoio para profissionais sobrecarregados'],
                'q' => ['Saímos daqui com a certeza de que ninguém precisa fazer jornalismo sozinho.', 'Marina Teles, repórter que participou do encontro'],
            ],
            [
                't' => 'Novos associados são recebidos em encontro de boas-vindas',
                'k' => 'Associados',
                's' => 'A recepção apresentou a história da associação, os grupos de estudo e a forma de participar das atividades ao longo do ano.',
                'c' => 'institucional',
                'p' => [
                    'Os novos associados foram recebidos com um café e uma apresentação sobre a trajetória da entidade, desde as primeiras reuniões, feitas na sala de um dos fundadores, até a estrutura atual.',
                    'Em seguida, os coordenadores dos grupos de estudo explicaram como funcionam os encontros mensais e como cada pessoa pode propor temas e convidar palestrantes.',
                ],
                'h' => 'Como a associação se organiza',
                'l' => ['Grupos de estudo com encontros mensais', 'Comissão de ética e boas práticas', 'Equipe de comunicação formada por voluntários'],
                'q' => ['Cada pessoa que chega traz uma experiência que a gente ainda não tinha.', 'Rui Matoso, coordenador dos grupos de estudo'],
            ],
            [
                't' => 'Roda de conversa discute o papel da comunicação nas comunidades',
                'k' => 'Debate',
                's' => 'Comunicadores de rádios, jornais e páginas de bairro trocaram experiências sobre como informar com responsabilidade quem vive perto.',
                'c' => 'institucional',
                'p' => [
                    'A conversa reuniu profissionais de veículos de diferentes tamanhos, de rádios comunitárias a perfis de bairro nas redes sociais. O ponto em comum foi a proximidade com o público, que cobra e corrige quase em tempo real.',
                    'Os participantes falaram sobre a importância de ouvir os moradores antes de publicar e de explicar ao leitor como a informação foi apurada.',
                ],
                'h' => 'Pontos levantados na conversa',
                'l' => ['Ouvir mais de uma fonte, mesmo em notícias pequenas', 'Corrigir erros de forma clara e visível', 'Explicar ao público como a notícia foi apurada'],
                'q' => ['A credibilidade de um veículo de bairro se constrói na calçada, conversando.', 'Joana Brites, comunicadora comunitária'],
            ],
            [
                't' => 'Associados visitam redação de jornal centenário do interior',
                'k' => 'Visita',
                's' => 'O grupo conheceu as máquinas antigas preservadas pela família que fundou o jornal e conversou com a equipe que hoje produz a edição digital.',
                'c' => 'institucional',
                'p' => [
                    'A visita começou pelo antigo salão de impressão, onde ainda estão a primeira prensa e os tipos de chumbo usados nas primeiras edições. Os visitantes puderam ver de perto como uma página era montada letra por letra.',
                    'No andar de cima, a equipe atual mostrou como o jornal passou a publicar na internet sem abandonar a edição semanal impressa, que ainda circula entre os leitores mais antigos.',
                ],
                'h' => 'O que o grupo conheceu',
                'l' => ['A prensa original e o acervo de edições antigas', 'A rotina da redação digital', 'O projeto de digitalização do arquivo do jornal'],
                'q' => ['O papel mudou, mas o compromisso com a cidade é o mesmo de cem anos atrás.', 'Heitor Campos, editor do jornal visitado'],
            ],
            [
                't' => 'Diretoria apresenta balanço das atividades do ano',
                'k' => 'Balanço',
                's' => 'O relatório registra encontros, cursos e ações de apoio a profissionais, além das metas para o próximo período.',
                'c' => 'institucional',
                'p' => [
                    'O balanço foi apresentado em reunião aberta aos associados. Entre os destaques, estão os encontros regionais, os cursos de formação e a criação de uma rede de apoio entre profissionais.',
                    'A diretoria também apresentou as metas para o próximo ano, que incluem ampliar a participação de associados do interior e organizar o acervo histórico da entidade.',
                ],
                'h' => 'Metas para o próximo período',
                'l' => ['Mais encontros fora da capital', 'Organização do acervo histórico', 'Novos grupos de estudo temáticos'],
                'q' => ['O que fizemos este ano só foi possível porque muita gente doou seu tempo.', 'Sílvia Porto, integrante da diretoria'],
            ],
            [
                't' => 'Palestra relembra a trajetória de jornalistas pioneiros da região',
                'k' => 'Memória',
                's' => 'Fotografias, recortes e depoimentos ajudaram a contar a história de quem abriu caminho para a imprensa local.',
                'c' => 'institucional',
                'p' => [
                    'A palestra percorreu sete décadas de história da imprensa regional, com fotografias e recortes guardados por famílias e antigos colaboradores.',
                    'O público se emocionou com depoimentos gravados de profissionais que começaram a carreira como entregadores de jornal e chegaram a dirigir redações.',
                ],
                'h' => 'Destaques da apresentação',
                'l' => ['O primeiro jornal impresso da região', 'A chegada do rádio às pequenas cidades', 'As primeiras mulheres nas redações'],
                'q' => ['Contar essa história é também uma forma de agradecer.', 'Beatriz Leme, pesquisadora responsável pela palestra'],
            ],
            [
                't' => 'Associação participa de feira de comunicação com estande próprio',
                'k' => 'Evento',
                's' => 'No estande, os visitantes conheceram o trabalho da entidade, folhearam edições antigas e conversaram com associados.',
                'c' => 'institucional',
                'p' => [
                    'Durante os três dias de feira, o estande recebeu estudantes, profissionais e curiosos interessados em conhecer o trabalho da associação.',
                    'Uma das atrações foi a mesa com edições antigas de jornais da região, que despertou conversas sobre como a cobertura dos assuntos mudou ao longo dos anos.',
                ],
                'h' => 'O que havia no estande',
                'l' => ['Mostra de edições antigas de jornais', 'Bate-papo com associados', 'Material sobre os grupos de estudo'],
                'q' => ['Muitos estudantes saíram daqui com vontade de conhecer melhor a profissão.', 'Caio Fontes, voluntário do estande'],
            ],

            // ---- Cidades ----------------------------------------------------
            [
                't' => 'Bairros da zona norte ganham linha de ônibus noturna',
                'k' => 'Mobilidade',
                's' => 'A linha circula entre 23h e 5h e atende trabalhadores que saem tarde do centro.',
                'c' => 'cidades',
                'p' => [
                    'A nova linha foi um pedido antigo dos moradores, que dependiam de transporte por aplicativo ou de longas caminhadas para voltar para casa depois do trabalho.',
                    'Nas primeiras semanas, os ônibus vão passar a cada 40 minutos. O intervalo pode diminuir se a procura for maior que a esperada.',
                ],
                'h' => 'Como vai funcionar',
                'l' => ['Saídas a cada 40 minutos', 'Doze pontos de parada no trajeto', 'Mesma tarifa das linhas diurnas'],
                'q' => ['Agora dá para sair do turno sem medo de não ter como voltar.', 'Neide Prado, moradora da zona norte'],
            ],
            [
                't' => 'Mutirão de limpeza recupera praças e canteiros do centro',
                'k' => 'Zeladoria',
                's' => 'Voluntários e equipes municipais recolheram entulho, plantaram mudas e repintaram bancos em seis praças.',
                'c' => 'cidades',
                'p' => [
                    'O mutirão começou às 7h e reuniu mais de cem pessoas, entre moradores, comerciantes e servidores municipais. Cada grupo ficou responsável por uma praça.',
                    'Além da limpeza, foram plantadas mudas de árvores nativas e instaladas novas lixeiras. Os organizadores pretendem repetir a ação a cada três meses.',
                ],
                'h' => 'O que foi feito',
                'l' => ['Retirada de quatro caminhões de entulho', 'Plantio de oitenta mudas nativas', 'Pintura de bancos e brinquedos'],
                'q' => ['A praça é a sala de visitas do bairro, e hoje ela ficou bonita de novo.', 'Lauro Pires, comerciante do centro'],
            ],
            [
                't' => 'Feira de produtores rurais passa a funcionar também aos domingos',
                'k' => 'Economia',
                's' => 'A ampliação atende a pedidos dos moradores e deve aumentar a renda dos pequenos agricultores da região.',
                'c' => 'cidades',
                'p' => [
                    'A feira, que até agora funcionava só aos sábados, passou a montar as barracas também aos domingos pela manhã. Frutas, verduras, queijos e pães caseiros estão entre os produtos mais procurados.',
                    'Os produtores contam que a venda direta ao consumidor garante um preço melhor e ajuda a manter as famílias no campo.',
                ],
                'h' => 'O que se encontra na feira',
                'l' => ['Frutas e verduras da estação', 'Queijos, doces e pães caseiros', 'Mudas e plantas ornamentais'],
                'q' => ['Vender direto para quem come o que a gente planta faz toda a diferença.', 'Dirce Amaral, produtora rural'],
            ],
            [
                't' => 'Escolas municipais ganham hortas cuidadas por alunos e famílias',
                'k' => 'Educação',
                's' => 'O projeto envolve estudantes, professores e famílias no cultivo de verduras que reforçam a merenda escolar.',
                'c' => 'cidades',
                'p' => [
                    'Cada escola recebeu canteiros, ferramentas e sementes. Os alunos se revezam nos cuidados com as plantas e aprendem, na prática, conteúdos de ciências e matemática.',
                    'As famílias participam nos fins de semana, e parte da colheita vai para a merenda. O restante é dividido entre os participantes.',
                ],
                'h' => 'O que os alunos aprendem',
                'l' => ['Ciclo das plantas e cuidados com o solo', 'Medidas, áreas e contagem na prática', 'Alimentação saudável'],
                'q' => ['Os alunos que mais faltavam agora chegam cedo para regar a horta.', 'Ana Paula Viegas, professora de ciências'],
            ],
            [
                't' => 'Ponte entre bairros é liberada após seis meses de obras',
                'k' => 'Obras',
                's' => 'A estrutura nova tem calçada mais larga, ciclovia e iluminação, e encurta o trajeto de quem vai ao centro.',
                'c' => 'cidades',
                'p' => [
                    'A ponte antiga, estreita e sem calçada, foi substituída por uma estrutura com duas faixas para carros, ciclovia e passeio para pedestres dos dois lados.',
                    'Moradores dos dois bairros acompanharam a liberação e comemoraram o fim dos desvios, que aumentavam em até vinte minutos o trajeto até o centro.',
                ],
                'h' => 'O que mudou',
                'l' => ['Ciclovia nos dois sentidos', 'Calçadas largas e acessíveis', 'Iluminação em toda a extensão'],
                'q' => ['Agora meu filho vai para a escola de bicicleta, com segurança.', 'Rogério Faria, morador do bairro vizinho'],
            ],
            [
                't' => 'Posto de saúde amplia horário de vacinação',
                'k' => 'Saúde',
                's' => 'A unidade passa a vacinar até as 20h nos dias úteis, o que facilita a vida de quem trabalha durante o dia.',
                'c' => 'cidades',
                'p' => [
                    'A mudança foi pensada para quem não consegue sair do trabalho em horário comercial. Nas primeiras noites, a procura foi maior entre adultos que estavam com doses atrasadas.',
                    'A equipe reforça que é importante levar a caderneta de vacinação e um documento com foto.',
                ],
                'h' => 'O que levar',
                'l' => ['Caderneta de vacinação', 'Documento com foto', 'Cartão do SUS, se tiver'],
                'q' => ['Muita gente estava com a vacina atrasada por falta de horário.', 'Elisa Moura, enfermeira da unidade'],
            ],

            // ---- Capital ----------------------------------------------------
            [
                't' => 'Biblioteca pública abre aos sábados com programação para crianças',
                'k' => 'Leitura',
                's' => 'Contação de histórias, oficinas de desenho e empréstimo de livros fazem parte da agenda de fim de semana.',
                'c' => 'capital',
                'p' => [
                    'A biblioteca passou a abrir aos sábados pela manhã, com uma programação pensada para as famílias. A sala infantil foi reorganizada, com tapetes, almofadas e prateleiras baixas.',
                    'Nos primeiros fins de semana, as sessões de contação de histórias lotaram a sala, e a biblioteca registrou recorde de novos cadastros.',
                ],
                'h' => 'A programação de sábado',
                'l' => ['Contação de histórias às 10h', 'Oficina de desenho às 11h', 'Empréstimo de livros a manhã toda'],
                'q' => ['Ver as crianças disputando livro é a melhor coisa deste trabalho.', 'Teresa Lins, bibliotecária'],
            ],
            [
                't' => 'Centro histórico ganha roteiro a pé guiado por estudantes',
                'k' => 'Turismo',
                's' => 'Os passeios contam a história de prédios, ruas e personagens que marcaram a formação da cidade.',
                'c' => 'capital',
                'p' => [
                    'O roteiro foi montado por estudantes de história e turismo, que pesquisaram em arquivos e conversaram com moradores antigos do centro.',
                    'O percurso tem cerca de dois quilômetros e passa por igrejas, casarões, praças e pelo antigo prédio dos correios.',
                ],
                'h' => 'Paradas do roteiro',
                'l' => ['Praça da matriz e o coreto', 'Casarões da rua principal', 'Antigo prédio dos correios'],
                'q' => ['Cada fachada tem uma história que pouca gente conhece.', 'Felipe Arantes, estudante de história'],
            ],
            [
                't' => 'Parque urbano ganha pista de caminhada iluminada',
                'k' => 'Lazer',
                's' => 'A nova pista tem dois quilômetros, piso antiderrapante e pontos de hidratação ao longo do percurso.',
                'c' => 'capital',
                'p' => [
                    'A pista contorna o lago do parque e foi pensada para quem caminha ou corre de manhã cedo e à noite. A iluminação nova cobre todo o trajeto.',
                    'Também foram instalados aparelhos de ginástica e bancos à sombra das árvores.',
                ],
                'h' => 'O que há no percurso',
                'l' => ['Dois quilômetros de pista iluminada', 'Bebedouros a cada quinhentos metros', 'Aparelhos de ginástica ao ar livre'],
                'q' => ['Agora dá para caminhar depois do trabalho sem medo do escuro.', 'Osvaldo Reis, frequentador do parque'],
            ],
            [
                't' => 'Reforma do mercado municipal será feita por etapas',
                'k' => 'Obras',
                's' => 'O plano permite que os comerciantes continuem atendendo enquanto cada ala do prédio é reformada.',
                'c' => 'capital',
                'p' => [
                    'O mercado, um dos prédios mais antigos do centro, vai passar por reforma no telhado, na parte elétrica e nos banheiros. As obras serão feitas em quatro etapas.',
                    'Os comerciantes da ala em reforma serão transferidos para boxes provisórios no estacionamento, montados antes do início de cada etapa.',
                ],
                'h' => 'As etapas da reforma',
                'l' => ['Telhado e calhas', 'Rede elétrica e iluminação', 'Banheiros e acessibilidade'],
                'q' => ['O mercado é parte da vida da cidade, e não podia simplesmente fechar.', 'Gilda Rocha, presidente da associação dos comerciantes'],
            ],
            [
                't' => 'Conselho de moradores discute plano para o trânsito do centro',
                'k' => 'Cidadania',
                's' => 'A proposta prevê mais faixas de pedestres, horários para carga e descarga e estacionamento rotativo.',
                'c' => 'capital',
                'p' => [
                    'A reunião do conselho ocupou o salão paroquial e teve participação de moradores, comerciantes e técnicos de trânsito. O plano ainda está em discussão.',
                    'Entre as propostas, a que mais gerou debate foi a restrição de caminhões de entrega no horário de pico.',
                ],
                'h' => 'O que está em discussão',
                'l' => ['Novas faixas de pedestres perto das escolas', 'Horário fixo para carga e descarga', 'Estacionamento rotativo nas ruas comerciais'],
                'q' => ['O centro precisa ser bom para quem mora, para quem trabalha e para quem visita.', 'Márcio Leal, integrante do conselho de moradores'],
            ],

            // ---- Cultura ----------------------------------------------------
            [
                't' => 'Festival de inverno reúne 40 apresentações em praças e teatros',
                'k' => 'Agenda cultural',
                's' => 'Música, teatro e dança ocuparam a região durante duas semanas de programação gratuita.',
                'c' => 'cultura',
                'p' => [
                    'O festival levou artistas da região e de outros estados para praças, teatros e escolas. As apresentações ao ar livre reuniram famílias inteiras, mesmo nas noites mais frias.',
                    'Os organizadores destacaram a participação de grupos locais, que dividiram o palco com artistas mais conhecidos.',
                ],
                'h' => 'Destaques do festival',
                'l' => ['Concerto de abertura na praça central', 'Mostra de teatro de rua', 'Oficinas de dança para crianças'],
                'q' => ['Tocar para a nossa própria cidade tem um gosto diferente.', 'Bruna Sales, cantora de um grupo local'],
            ],
            [
                't' => 'Feira do livro homenageia cronistas da imprensa regional',
                'k' => 'Literatura',
                's' => 'A edição destacou autores que começaram nos jornais e transformaram o cotidiano em literatura.',
                'c' => 'cultura',
                'p' => [
                    'Os cronistas homenageados têm em comum o começo nas páginas dos jornais da região, onde publicavam textos curtos sobre a vida na cidade.',
                    'A feira teve mesas de conversa, sessões de autógrafos e um espaço com crônicas antigas recortadas de jornais, emolduradas para os visitantes lerem.',
                ],
                'h' => 'A programação',
                'l' => ['Mesas de conversa com os homenageados', 'Sessões de autógrafos', 'Mural com crônicas históricas'],
                'q' => ['A crônica é o jornalismo que se permite olhar devagar.', 'Otávio Guerra, cronista homenageado'],
            ],
            [
                't' => 'Mostra reúne fotografias históricas de jornais da região',
                'k' => 'Exposição',
                's' => 'As imagens, cedidas por acervos de família e de redações, contam décadas da história local.',
                'c' => 'cultura',
                'p' => [
                    'A exposição reúne cerca de cem fotografias, algumas inéditas, publicadas originalmente em jornais da região. As imagens mostram enchentes, festas, inaugurações e o cotidiano das ruas.',
                    'Cada foto vem acompanhada da página em que foi publicada, o que permite comparar a imagem com o texto da época.',
                ],
                'h' => 'Os núcleos da mostra',
                'l' => ['Ruas e paisagens que mudaram', 'Festas populares', 'Grandes acontecimentos da região'],
                'q' => ['Essas fotos são a memória visual da cidade.', 'Helena Prado, curadora da mostra'],
            ],
            [
                't' => 'Coral comemora 30 anos com concerto ao ar livre',
                'k' => 'Música',
                's' => 'O repertório revisitou canções que marcaram a trajetória do grupo e incluiu uma obra inédita.',
                'c' => 'cultura',
                'p' => [
                    'O concerto reuniu integrantes atuais e antigos do coral, que voltaram para cantar algumas das músicas mais pedidas ao longo das três décadas.',
                    'A obra inédita foi composta especialmente para a comemoração por um dos fundadores do grupo.',
                ],
                'h' => 'O repertório',
                'l' => ['Canções populares em arranjo coral', 'Peças do repertório sacro', 'Obra inédita composta para os 30 anos'],
                'q' => ['O coral é uma família que se encontra para cantar.', 'Augusto Neves, regente do coral'],
            ],
            [
                't' => 'Cineclube exibe documentários produzidos por estudantes',
                'k' => 'Cinema',
                's' => 'As sessões foram seguidas de conversa com os realizadores sobre os bastidores das produções.',
                'c' => 'cultura',
                'p' => [
                    'Os documentários tratam de temas próximos dos estudantes: a história de um bairro, o trabalho de uma costureira, a rotina de uma escola rural.',
                    'Depois de cada sessão, o público conversou com os realizadores, que contaram como foi filmar com equipamentos simples e pouco tempo.',
                ],
                'h' => 'Os filmes exibidos',
                'l' => ['Retrato de um bairro operário', 'A costureira da rua de cima', 'Um dia na escola rural'],
                'q' => ['Fazer um documentário ensina a ouvir.', 'Laís Monteiro, estudante e realizadora'],
            ],
            [
                't' => 'Sarau literário reúne leitores e autores locais',
                'k' => 'Encontro',
                's' => 'Poemas, crônicas e contos foram lidos no microfone aberto durante a noite.',
                'c' => 'cultura',
                'p' => [
                    'O sarau ocupou o café de uma livraria e teve inscrições feitas na hora. Leram autores estreantes e escritores com livros publicados.',
                    'Entre uma leitura e outra, músicos tocaram canções que dialogavam com os textos apresentados.',
                ],
                'h' => 'Como foi a noite',
                'l' => ['Microfone aberto para leitores', 'Intervalos musicais', 'Troca de livros entre os participantes'],
                'q' => ['Ler em voz alta para estranhos é um ato de coragem e de confiança.', 'Vera Lúcia Assis, poeta'],
            ],

            // ---- Formação ---------------------------------------------------
            [
                't' => 'Curso de checagem de fatos forma primeira turma',
                'k' => 'Curso',
                's' => 'As aulas mostraram como verificar imagens, dados e declarações antes da publicação.',
                'c' => 'formacao',
                'p' => [
                    'Durante quatro semanas, os participantes praticaram a verificação de imagens, a busca da origem de vídeos e a conferência de números citados por fontes oficiais.',
                    'No último encontro, cada aluno apresentou a checagem de uma informação que circulou nas redes sociais da região.',
                ],
                'h' => 'O que foi ensinado',
                'l' => ['Busca reversa de imagens', 'Conferência de dados públicos', 'Como publicar uma correção'],
                'q' => ['Checar não atrasa a notícia: evita que ela precise ser desmentida.', 'Paulo Serra, instrutor do curso'],
            ],
            [
                't' => 'Oficina ensina fotojornalismo com o celular',
                'k' => 'Oficina',
                's' => 'Os participantes aprenderam composição, luz e edição básica para produzir boas imagens com o telefone.',
                'c' => 'formacao',
                'p' => [
                    'A oficina teve uma parte teórica, sobre composição e luz natural, e uma saída fotográfica pelo centro da cidade.',
                    'No fim, as melhores imagens foram projetadas e comentadas pelo grupo.',
                ],
                'h' => 'Dicas da oficina',
                'l' => ['Aproveitar a luz natural', 'Fotografar na altura dos olhos das pessoas', 'Editar pouco e manter a imagem fiel'],
                'q' => ['O melhor equipamento é o que está no seu bolso na hora certa.', 'Raquel Dantas, fotojornalista'],
            ],
            [
                't' => 'Ciclo de palestras debate ética na cobertura de temas sensíveis',
                'k' => 'Palestras',
                's' => 'Profissionais experientes discutiram como tratar com cuidado temas como luto, saúde mental e violência.',
                'c' => 'formacao',
                'p' => [
                    'O ciclo teve três encontros, cada um dedicado a um tema. Os palestrantes trouxeram exemplos de coberturas que acertaram e de outras que causaram sofrimento desnecessário.',
                    'O público, formado principalmente por estudantes, participou com perguntas sobre situações do dia a dia das redações.',
                ],
                'h' => 'Cuidados discutidos',
                'l' => ['Respeitar o tempo das famílias', 'Evitar detalhes que não informam', 'Indicar serviços de apoio ao leitor'],
                'q' => ['Informar com cuidado também é informar bem.', 'Mônica Valadares, jornalista e professora'],
            ],
            [
                't' => 'Programa de mentoria aproxima estudantes de jornalistas experientes',
                'k' => 'Mentoria',
                's' => 'Durante seis meses, cada estudante acompanhou a rotina de um profissional e produziu uma reportagem.',
                'c' => 'formacao',
                'p' => [
                    'Os pares de mentoria se encontraram a cada quinze dias. Os estudantes acompanharam reuniões de pauta, entrevistas e o fechamento de edições.',
                    'Ao final, as reportagens produzidas pelos estudantes foram reunidas numa publicação digital.',
                ],
                'h' => 'Como funcionou',
                'l' => ['Encontros quinzenais com o mentor', 'Acompanhamento da rotina da redação', 'Uma reportagem produzida por estudante'],
                'q' => ['Aprendi mais acompanhando uma apuração do que em muitos livros.', 'Igor Bastos, estudante participante'],
            ],
            [
                't' => 'Workshop mostra como produzir podcasts jornalísticos',
                'k' => 'Áudio',
                's' => 'O encontro abordou roteiro, gravação, edição e distribuição de programas em áudio.',
                'c' => 'formacao',
                'p' => [
                    'O workshop mostrou que é possível começar um podcast com poucos recursos: um bom roteiro, um ambiente silencioso e um gravador simples.',
                    'Os participantes gravaram um episódio piloto em grupo, editado ao vivo pelos instrutores.',
                ],
                'h' => 'Etapas de um podcast',
                'l' => ['Pauta e roteiro', 'Gravação em ambiente silencioso', 'Edição e publicação'],
                'q' => ['No áudio, a voz do entrevistado carrega a emoção que o texto às vezes perde.', 'Diego Amaral, produtor de podcasts'],
            ],
            [
                't' => 'Guia reúne cuidados de segurança para coberturas de rua',
                'k' => 'Guia',
                's' => 'A publicação traz orientações sobre equipamentos, deslocamento e apoio da equipe durante as coberturas.',
                'c' => 'formacao',
                'p' => [
                    'O guia foi escrito a partir de relatos de repórteres e fotógrafos que cobrem manifestações, eventos e situações de risco.',
                    'Ele traz uma lista de verificação para antes de sair da redação e orientações sobre o que fazer em caso de emergência.',
                ],
                'h' => 'Antes de sair para a rua',
                'l' => ['Avisar a equipe sobre o trajeto', 'Levar bateria extra e documento', 'Combinar um ponto de encontro'],
                'q' => ['Nenhuma imagem vale mais do que a integridade de quem a produz.', 'Renato Lobo, fotógrafo'],
            ],
        ];
    }
}
