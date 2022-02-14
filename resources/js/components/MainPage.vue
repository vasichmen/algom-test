<template>
    <div class="page-box">
        <div class="menu">
            <ul class="menu__list">
                <li class="menu__item">
                    <button @click="handleCheckLinks">
                        Проверить ссылки
                    </button>
                </li>
            </ul>
        </div>

        <div class="checker-container">
            <div class="checker-container__item">
                <div class="checker-header">
                    HTML
                </div>
                <div class="menu editor-action-panel">
                    <ul class="menu__list">
                        <li class="menu__item">
                            <button @click="handleAddLink">
                                Добавить ссылку
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="editor-text-field">
                    <textarea
                        v-model="markup"
                        class="editor-text-field__textarea"
                        name="markup"
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
            }
            ;
        },

        watch: {
            markup(newMarkup) {
                if (!newMarkup) {
                    this.abortAllRequests();
                    this.report = [];
                }
                else {
                    this.checkLinks(newMarkup);
                }
            },
        },

        methods: {
            handleCheckLinks() {
                this.checkLinks(this.markup);
            },
            handleAddLink() {
                console.log('add link');
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
                    this.report = [];
                }

                this.isChecking = true;

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
                while (this.freeThreads === 0) {
                    await new Promise(r => setTimeout(r, 100));
                }

                //уменьшаем число совбодных потоков
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
