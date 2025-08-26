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
    id: 'faq_5',
    question: 'Охотно ли меняю позы?',
    type: 'radio',
    options: [
      { value: 1, label: 'Нет, предпочитаю, чтобы партнер был сверху или "догги стайл"' },
      { value: 2, label: 'Да, в процессе могу несколько раз сменить позу по запросу клиента' },
      { value: 3, label: 'Да, становлюсь в любую позу, могу взять инициативу и сесть на клиента сверху' }
    ]
  },
  // === НОВЫЕ ПАРАМЕТРЫ ИЗ FEATURES ===
  {
    id: 'faq_25',
    question: 'Курите?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, курю' },
      { value: 2, label: 'Не курю' }
    ]
  },
  {
    id: 'faq_26',
    question: 'Работаете в критические дни?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, работаю в критические дни' },
      { value: 2, label: 'Нет, в критические дни не работаю' }
    ]
  },
  {
    id: 'faq_27',
    question: 'Есть татуировки?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, есть татуировки' },
      { value: 2, label: 'Нет татуировок' }
    ]
  },
  {
    id: 'faq_28',
    question: 'Есть пирсинг?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, есть пирсинг' },
      { value: 2, label: 'Нет пирсинга' }
    ]
  },
  {
    id: 'faq_29',
    question: 'Есть автомобиль?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, есть автомобиль' },
      { value: 2, label: 'Нет автомобиля' }
    ]
  },
  {
    id: 'faq_30',
    question: 'Работаете в выходные дни?',
    type: 'radio',
    options: [
      { value: 1, label: 'Да, работаю в выходные' },
      { value: 2, label: 'Не работаю в выходные' }
    ]
  }
]