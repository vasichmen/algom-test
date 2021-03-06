<template>
    <div class="page-box">
        <div class="checker-container">
            <div class="checker-container__item">
                <div class="checker-header">
                    HTML
                </div>
                <div class="editor-action-panel">
                    <button @click="handleAddLink">
                        Добавить ссылку
                    </button>
                    <!-- todo: сделать добавление ссылки через модалку -->
                    <div>
                        <input
                            v-model="newLink.text"
                            type="text"
                            placeholder="Текст ссылки"
                        />
                        <input
                            v-model="newLink.href"
                            type="text"
                            placeholder="Адрес ссылки"
                        />
                    </div>
                </div>
                <div class="editor-text-field">
                    <textarea
                        id="markup"
                        v-model="markup"
                        class="editor-text-field__textarea"
                    ></textarea>
                </div>
            </div>
            <div class="checker-container__item">
                <div class="checker-header">
                    Preview
                </div>
                <div class="preview">
                    <div v-html="markup"></div>
                </div>
            </div>
            <div class="checker-container__item">
                <div class="checker-header">
                    Links Report
                </div>
                <div class="report">
                    <ul
                        class="report__list"
                    >
                        <li
                            v-for="item in report"
                            :key="item.id"
                            :class="getReportItemClasses(item)"
                        >
                            <div>
                                Текст ссылки: {{ item.text }} <br />
                                Адрес ссылки: {{ item.href }} <br />
                                Статус: {{ item.status }}
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import axios from 'axios';

    const PARALLEL_COUNT = 2;
    const SUCCESS_STATUSES = [200];

    export default {
        name: 'main-page',

        data: function () {
            return {
                markup: '',
                report: [],

                freeThreads: PARALLEL_COUNT,
                requests: [],
                isChecking: false,

                newLink: {
                    text: '',
                    href: '',
                },
            }
            ;
        },

        watch: {
            markup(newMarkup) {
                if (!newMarkup) {
                    this.abortAllRequests();
                }
                else {
                    this.checkLinks(newMarkup);
                }
            },
        },

        methods: {
            handleAddLink() {
                let startPosition = document.getElementById('markup')?.selectionStart;
                if (startPosition === undefined) {
                    return;
                }

                let linkMarkup = `<a href="${this.newLink.href}">${this.newLink.text}</a>`;
                this.markup = `${this.markup.slice(0, startPosition)}${linkMarkup}${this.markup.slice(startPosition)}`;
            },
            getReportItemClasses(item) {
                return {
                    'report__list-item': true,
                    'report__list-item--green': SUCCESS_STATUSES.includes(item.status),
                };
            },


            /**
             * проверка ссылок и заполнение this.report
             * @param markup
             */
            async checkLinks(markup) {
                //если обработка еще не завершена, то останавливаем
                if (this.isChecking) {
                    this.abortAllRequests();
                }

                this.isChecking = true;
                this.report = [];

                //чтоб достать все ссылки из верстки создандим новый объект DOM
                let rootElement = document.createElement('div');
                rootElement.innerHTML = markup;
                let links = [];
                rootElement.querySelectorAll('a').forEach(link => {
                    links.push({
                        href: link.getAttribute('href'),
                        text: link.innerText,
                    });
                });

                //todo: можно сделать проверку на уникальность ссылок перед началом проверки, но тогда результат придется выводить по исходным данным, а статусы ссылок брать из ответов бэка.
                await this.fillReport(links);

                this.isChecking = false;
                this.freeThreads = PARALLEL_COUNT;
            },

            /**
             * проверка ссылок
             * @param links
             */
            async fillReport(links) {
                let promises = [];

                links.forEach((link) => {
                    promises.push(this.callRequest(link));
                });

                return Promise.all(promises);
            },

            /**
             * Отмена всех начатых запросов
             */
            abortAllRequests() {
                this.requests.forEach(controller => controller.abort());
                this.requests = [];
            },

            /**
             * отправка запроса на бэк для проверки ссылки. (чтоб обойти проверку CORS)
             * @param link
             * @returns {Promise<unknown>}
             */
            async callRequest(link) {
                //ждем сободного потока для работы
                while (this.freeThreads === 0) {
                    await new Promise(r => setTimeout(r, 200));
                }

                //уменьшаем число свободных потоков
                this.freeThreads--;

                //отправляем запрос на бэк
                let controller = new AbortController;
                this.requests.push(controller);
                return axios.get(`/api/v1/check-link`, {
                    params: { link: link.href },
                    signal: controller.signal,
                }).then((response) => {
                    this.report.push(
                        {
                            ...link,
                            status: response.data.code,
                        });
                }).finally(() => {
                    this.freeThreads++; //по завершении запроса увеличиваем число потоков обратно
                });
            },
        },
    };
</script>
