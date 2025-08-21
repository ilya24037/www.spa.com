export interface FaqOption {
  value: string | number
  label: string
}

export interface FaqQuestion {
  id: string
  question: string
  type: 'radio' | 'checkbox'
  options: FaqOption[]
  allowMultiple?: boolean // для checkbox с "Выбрать все"
}

export const faqQuestions: FaqQuestion[] = [
  {
    id: 'faq_1',
    question: 'Возможен первый опыт с девушкой?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, принимаю парней-девственников (18+)' },
      { value: 2, label: 'Нет' }
    ]
  },
  {
    id: 'faq_2',
    question: 'Есть ласки и тактильный контакт?',
    type: 'checkbox',
    allowMultiple: true,
    options: [
      { value: 1, label: 'Да, можно ласкать грудь, гладить тело, прикасаться к интимным местам' },
      { value: 2, label: 'Да, целуюсь' },
      { value: 3, label: 'Да, ласкаю тебя руками и губами' },
      { value: 4, label: 'Нет' }
    ]
  },
  {
    id: 'faq_3',
    question: 'Возможны встречи в формате GFE?',
    type: 'checkbox',
    allowMultiple: true,
    options: [
      { value: 1, label: 'Да, хожу с клиентом на свидание в бар, ресторан, кафе' },
      { value: 2, label: 'Да, можем сходить в кино или театр, на массовое мероприятие' },
      { value: 3, label: 'Да, обеспечиваю свидание у себя в апартаментах' },
      { value: 4, label: 'Нет' }
    ]
  },
  {
    id: 'faq_5',
    question: 'Охотно ли меняю позы?',
    type: 'radio',
    options: [
      { value: 1, label: 'Нет, предпочитаю, чтобы партнер был сверху или "догги стайл"' },
      { value: 2, label: 'Да, в процессе могу несколько раз сменить позу по запросу клиента' },
      { value: 3, label: 'Да, становлюсь в любую позу, могу взять инициативу и сесть на клиента сверху' }
    ]
  },
  {
    id: 'faq_4',
    question: 'Возможно совместное распитие спиртных напитков?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, выпиваю, спиртное имеется в наличии за отдельную плату' },
      { value: 2, label: 'Могу выпить, если клиент принесет алкоголь с собой' },
      { value: 3, label: 'Не пью, но позволяю выпивать в моем присутствии, могу поддержать беседу' },
      { value: 4, label: 'Строго нет' }
    ]
  },
  {
    id: 'faq_6',
    question: 'Возможны интимно-спонсорские отношения?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, по симпатии, если клиент понравился, то возможны регулярные встречи' },
      { value: 2, label: 'Да, могу перейти на полное содержание (перестану оказывать услуги другим клиентам)' },
      { value: 3, label: 'Нет, только разовые встречи' }
    ]
  },
  {
    id: 'faq_7',
    question: 'Пошлая и развратная?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Нет' }
    ]
  },
  {
    id: 'faq_22',
    question: 'Сквиртую?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Да, только от пальцев' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_8',
    question: 'Встречаюсь для проведения отличного отдыха',
    type: 'radio',
    options: [
      { value: 1, label: 'одна, строго индивидуально и без параллельных встреч' },
      { value: 2, label: 'с подругой' },
      { value: 3, label: 'с подругами' },
      { value: 4, label: 'не буду против с другом/друзьями клиента' }
    ]
  },
  {
    id: 'faq_9',
    question: 'Готова к экспериментам?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, люблю разнообразие и разные позы, со мной можно все' },
      { value: 2, label: 'Да, в зависимости от опыта клиента' },
      { value: 3, label: 'Нет, услуги предоставляет только в рамках анкеты' },
      { value: 4, label: 'Нет' }
    ]
  },
  {
    id: 'faq_10',
    question: 'Обеспечиваю анонимность и конфиденциальность?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, гарантирую' },
      { value: 2, label: 'Нет' }
    ]
  },
  {
    id: 'faq_11',
    question: 'Гарантирована приятная и интересная беседа?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Да, если останется время' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_12',
    question: 'Порадую эротическим и возбуждающим танцем?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, профессиональным' },
      { value: 2, label: 'Да, любительским' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_13',
    question: 'Девушка не принимает мужчин',
    type: 'radio',
    options: [
      { value: 1, label: 'Младше 20-30 лет' },
      { value: 2, label: 'В алкогольном и наркотическом опьянении' },
      { value: 3, label: 'Младше 20-30 лет, а также в алкогольном и наркотическом опьянении' }
    ]
  },
  {
    id: 'faq_16',
    question: 'Фото соответствуют действительности?',
    type: 'radio',
    options: [
      { value: 1, label: 'Однозначно ДА' },
      { value: 2, label: 'Фото были сделаны много лет назад' },
      { value: 3, label: 'Нет, это типажные фото' }
    ]
  },
  {
    id: 'faq_15',
    question: 'Имеются комплексы, предрассудки и стеснения?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Немного' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_17',
    question: 'Имеет значение национальность клиента?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, принимаю только мужчин славянской внешности' },
      { value: 2, label: 'Да, принимаю только мужчин определенной национальности' },
      { value: 3, label: 'Не имеет значения, но в зависимости от поведения и манеры общения мужчины' },
      { value: 4, label: 'Не имеет значения, но по рекомендации' },
      { value: 5, label: 'Не имеет значения' }
    ]
  },
  {
    id: 'faq_18',
    question: 'Регулярный медицинский контроль?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, имеются подтверждающие справки' },
      { value: 2, label: 'Да' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_21',
    question: 'Можно получить фото/видео девушки до интимной встречи?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Да, по желанию клиента' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_14',
    question: 'Нужно заранее позвонить девушке?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, обязательно, ведь она должна подготовиться и принять клиента должным образом' },
      { value: 2, label: 'По желанию клиента' },
      { value: 3, label: 'Нет' }
    ]
  },
  {
    id: 'faq_20',
    question: 'Приемлемы опоздания клиента?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Да, но только в пределах 5-10 минут с учетом того, что клиент заранее предупредил' },
      { value: 3, label: 'Нет' },
      { value: 4, label: 'Нет, номер сразу попадает в БАН и ЧС' }
    ]
  },
  {
    id: 'faq_19',
    question: 'Квартира под охраной?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, face control' },
      { value: 2, label: 'Нет' }
    ]
  },
  {
    id: 'faq_23',
    question: 'Есть заграничный паспорт?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да' },
      { value: 2, label: 'Нет' }
    ]
  },
  {
    id: 'faq_24',
    question: 'Знания английского языка?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, высокий уровень' },
      { value: 2, label: 'Да, разговорный уровень' },
      { value: 3, label: 'Нет' }
    ]
  }
]