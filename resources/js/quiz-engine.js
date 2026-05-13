import Alpine from 'alpinejs'
import Sortable from 'sortablejs'

/**
 * Alpine component for taking a quiz (quiz/show view).
 * Handles navigation, answer collection for all 7 question types,
 * and submit-readiness checks.
 */
Alpine.data('quizPlayer', (questions) => ({
  questions,
  currentQuestion: 0,
  answers: {},
  orderingTouched: {},
  sortableInstances: {},

  init() {
    this.$watch('currentQuestion', () => {
      this.$nextTick(() => this.ensureSortable())
    })
    this.$nextTick(() => this.ensureSortable())
  },

  get totalQuestions() {
    return this.questions.length
  },

  get progress() {
    return ((this.currentQuestion + 1) / this.totalQuestions) * 100
  },

  get allAnswered() {
    return this.questions.every((q, i) => this.isAnswered(i))
  },

  get canProceed() {
    return this.isAnswered(this.currentQuestion)
  },

  isAnswered(index) {
    const answer = this.answers[index]
    const q = this.questions[index]
    const type = q.type || 'single_choice'

    if (answer === undefined || answer === null) return false

    switch (type) {
      case 'single_choice':
      case 'true_false':
        return answer !== '' && answer !== undefined
      case 'multiple_choice':
        return Array.isArray(answer) && answer.length > 0
      case 'short_answer':
        return typeof answer === 'string' && answer.trim() !== ''
      case 'ordering':
        return !!this.orderingTouched[index]
      case 'matching':
        return typeof answer === 'object' && Object.keys(answer).length === (q.left || []).length
      case 'cloze':
        return Array.isArray(answer) && answer.length === (q.blanks || []).length && answer.every(v => String(v).trim() !== '')
      default:
        return answer !== undefined
    }
  },

  questionType(index) {
    return this.questions[index]?.type || 'single_choice'
  },

  // --- Single Choice ---
  selectSingleChoice(questionIdx, optionIdx) {
    this.answers[questionIdx] = String(optionIdx)
  },

  isSingleChoiceSelected(questionIdx, optionIdx) {
    return String(this.answers[questionIdx]) === String(optionIdx)
  },

  // --- Multiple Choice ---
  toggleMultipleChoice(questionIdx, optionIdx) {
    if (!Array.isArray(this.answers[questionIdx])) {
      this.answers[questionIdx] = []
    }
    const val = String(optionIdx)
    const arr = this.answers[questionIdx]
    const idx = arr.indexOf(val)
    if (idx > -1) {
      arr.splice(idx, 1)
    } else {
      arr.push(val)
    }
  },

  isMultipleChoiceSelected(questionIdx, optionIdx) {
    return Array.isArray(this.answers[questionIdx]) && this.answers[questionIdx].includes(String(optionIdx))
  },

  // --- True/False ---
  selectTrueFalse(questionIdx, value) {
    this.answers[questionIdx] = String(value)
  },

  isTrueFalseSelected(questionIdx, value) {
    return String(this.answers[questionIdx]) === String(value)
  },

  // --- Short Answer ---
  updateShortAnswer(questionIdx, value) {
    this.answers[questionIdx] = value
  },

  // --- Ordering (Drag & Drop) ---
  initOrdering(questionIdx, shuffledIndices) {
    this.answers[questionIdx] = shuffledIndices
  },

  ensureSortable() {
    const qi = this.currentQuestion
    const q = this.questions[qi]
    if ((q?.type || 'single_choice') !== 'ordering') return
    if (this.sortableInstances[qi]) return

    const el = document.querySelector(`[data-ordering-list="${qi}"]`)
    if (!el) return

    const self = this
    this.sortableInstances[qi] = Sortable.create(el, {
      animation: 200,
      ghostClass: 'opacity-30',
      chosenClass: 'ring-2 ring-brand-primary',
      onEnd() {
        self.orderingTouched[qi] = true
        const items = el.querySelectorAll('[data-order-idx]')
        self.answers[qi] = Array.from(items).map(item => item.dataset.orderIdx)
        // Update position numbers
        el.querySelectorAll('.ordering-position').forEach((span, i) => {
          span.textContent = (i + 1) + '.'
        })
        // Update hidden inputs
        el.querySelectorAll('.ordering-hidden-input').forEach((input, i) => {
          input.value = self.answers[qi][i]
        })
      }
    })
  },

  // --- Matching ---
  initMatchingAnswer(questionIdx) {
    if (!this.answers[questionIdx] || typeof this.answers[questionIdx] !== 'object') {
      this.answers[questionIdx] = {}
    }
  },

  selectMatch(questionIdx, leftIdx, rightIdx) {
    this.initMatchingAnswer(questionIdx)
    this.answers[questionIdx][leftIdx] = String(rightIdx)
  },

  getMatchValue(questionIdx, leftIdx) {
    return this.answers[questionIdx]?.[leftIdx] ?? ''
  },

  // --- Cloze ---
  initClozeAnswer(questionIdx, blankCount) {
    if (!Array.isArray(this.answers[questionIdx])) {
      this.answers[questionIdx] = Array(blankCount).fill('')
    }
  },

  updateClozeBlank(questionIdx, blankIdx, value) {
    if (!Array.isArray(this.answers[questionIdx])) {
      this.answers[questionIdx] = []
    }
    this.answers[questionIdx][blankIdx] = value
  },

  // --- Navigation ---
  next() {
    if (this.currentQuestion < this.totalQuestions - 1) {
      this.currentQuestion++
    }
  },

  prev() {
    if (this.currentQuestion > 0) {
      this.currentQuestion--
    }
  },

  goTo(index) {
    this.currentQuestion = index
  }
}))

/**
 * Alpine component for the quiz editor (trainer view).
 * Supports creating/editing all 7 question types.
 */
Alpine.data('quizEditor', (initialQuestions, initialPass) => ({
  questions: [],
  passPercentage: initialPass || 70,

  questionTypes: [
    { value: 'single_choice', label: 'Single Choice' },
    { value: 'multiple_choice', label: 'Multiple Choice' },
    { value: 'true_false', label: 'Wahr / Falsch' },
    { value: 'short_answer', label: 'Kurzantwort' },
    { value: 'ordering', label: 'Reihenfolge' },
    { value: 'matching', label: 'Zuordnung' },
    { value: 'cloze', label: 'Lückentext' },
  ],

  init() {
    this.questions = (initialQuestions || []).map(q => this.normalizeQuestion(q))
  },

  normalizeQuestion(q) {
    const type = q.type || 'single_choice'
    const base = { type, question: q.question || '' }

    switch (type) {
      case 'single_choice':
        return { ...base, options: q.options || ['', '', ''], correct: q.correct ?? 0 }
      case 'multiple_choice':
        return { ...base, options: q.options || ['', '', ''], correct: q.correct || [] }
      case 'true_false':
        return { ...base, correct: q.correct ?? true }
      case 'short_answer':
        return { ...base, accepted_answers: q.accepted_answers || [''] }
      case 'ordering':
        return { ...base, items: q.items || ['', '', ''], correct_order: q.correct_order || [0, 1, 2] }
      case 'matching':
        return { ...base, left: q.left || ['', ''], right: q.right || ['', ''], correct_pairs: q.correct_pairs || { 0: 0, 1: 1 } }
      case 'cloze':
        return { ...base, text_template: q.text_template || '', blanks: q.blanks || [{ accepted_answers: [''] }] }
      default:
        return { ...base, options: ['', '', ''], correct: 0 }
    }
  },

  addQuestion(type = 'single_choice') {
    this.questions.push(this.normalizeQuestion({ type, question: '' }))
  },

  removeQuestion(index) {
    this.questions.splice(index, 1)
  },

  changeType(index, newType) {
    const q = this.questions[index]
    this.questions[index] = this.normalizeQuestion({ type: newType, question: q.question })
  },

  // --- Single/Multiple Choice helpers ---
  addOption(qi) {
    this.questions[qi].options.push('')
  },

  removeOption(qi, oi) {
    this.questions[qi].options.splice(oi, 1)
    const q = this.questions[qi]
    if (q.type === 'single_choice' && q.correct >= q.options.length) {
      q.correct = 0
    }
    if (q.type === 'multiple_choice') {
      q.correct = q.correct.filter(c => c < q.options.length)
    }
  },

  toggleCorrectMultiple(qi, oi) {
    const q = this.questions[qi]
    if (!Array.isArray(q.correct)) q.correct = []
    const idx = q.correct.indexOf(oi)
    if (idx > -1) {
      q.correct.splice(idx, 1)
    } else {
      q.correct.push(oi)
    }
  },

  isCorrectMultiple(qi, oi) {
    return Array.isArray(this.questions[qi].correct) && this.questions[qi].correct.includes(oi)
  },

  // --- Short Answer helpers ---
  addAcceptedAnswer(qi) {
    this.questions[qi].accepted_answers.push('')
  },

  removeAcceptedAnswer(qi, ai) {
    this.questions[qi].accepted_answers.splice(ai, 1)
  },

  // --- Ordering helpers ---
  addOrderingItem(qi) {
    const q = this.questions[qi]
    q.items.push('')
    q.correct_order.push(q.items.length - 1)
  },

  removeOrderingItem(qi, ii) {
    const q = this.questions[qi]
    q.items.splice(ii, 1)
    q.correct_order = q.items.map((_, i) => i)
  },

  moveOrderingItem(qi, from, direction) {
    const q = this.questions[qi]
    const to = from + direction
    if (to < 0 || to >= q.correct_order.length) return
    const arr = [...q.correct_order]
    ;[arr[from], arr[to]] = [arr[to], arr[from]]
    q.correct_order = arr
  },

  // --- Matching helpers ---
  addMatchingPair(qi) {
    const q = this.questions[qi]
    const newIdx = q.left.length
    q.left.push('')
    q.right.push('')
    q.correct_pairs[newIdx] = newIdx
  },

  removeMatchingPair(qi, pi) {
    const q = this.questions[qi]
    q.left.splice(pi, 1)
    q.right.splice(pi, 1)
    const newPairs = {}
    q.left.forEach((_, i) => { newPairs[i] = i })
    q.correct_pairs = newPairs
  },

  // --- Cloze helpers ---
  addClozeBlank(qi) {
    this.questions[qi].blanks.push({ accepted_answers: [''] })
  },

  removeClozeBlank(qi, bi) {
    this.questions[qi].blanks.splice(bi, 1)
  },

  addClozeAccepted(qi, bi) {
    this.questions[qi].blanks[bi].accepted_answers.push('')
  },

  removeClozeAccepted(qi, bi, ai) {
    this.questions[qi].blanks[bi].accepted_answers.splice(ai, 1)
  }
}))

// Make Sortable available for ordering init in templates
window.Sortable = Sortable
