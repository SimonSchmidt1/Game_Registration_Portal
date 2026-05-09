# Porovnanie syntaxe frameworkov – Reaktívna väzba dát

Nasledujúca ukážka demonštruje implementáciu identickej funkcionality vo všetkých troch hlavných frontendových frameworkoch. Komponent obsahuje jedno vstupné pole a odsek, ktorý v reálnom čase zobrazuje zadaný text. Ide o najzákladnejšiu demonštráciu reaktívnej obojsmernej väzby dát (two-way data binding).

---

## Vue.js 3 (Composition API)

```vue
<template>
  <input v-model="message" placeholder="Zadaj text" />
  <p>Zadané: {{ message }}</p>
</template>

<script setup>
import { ref } from 'vue'
const message = ref('')
</script>
```

**Počet riadkov: 8**

---

## React 18 (Functional Component + Hooks)

```jsx
import { useState } from 'react';

function MessageInput() {
  const [message, setMessage] = useState('');

  return (
    <>
      <input
        value={message}
        onChange={(e) => setMessage(e.target.value)}
        placeholder="Zadaj text"
      />
      <p>Zadané: {message}</p>
    </>
  );
}

export default MessageInput;
```

**Počet riadkov: 18**

---

## Angular 17 (Standalone Component)

```typescript
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-message-input',
  standalone: true,
  imports: [FormsModule],
  template: `
    <input [(ngModel)]="message" placeholder="Zadaj text" />
    <p>Zadané: {{ message }}</p>
  `,
})
export class MessageInputComponent {
  message = '';
}
```

**Počet riadkov: 15**

---

## Zhrnutie porovnania

| Kritérium                        | Vue.js 3       | React 18           | Angular 17          |
|----------------------------------|----------------|--------------------|---------------------|
| Počet riadkov                    | **8**          | 18                 | 15                  |
| Obojsmerná väzba dát            | `v-model`      | manuálny `onChange` | `[(ngModel)]`       |
| Potrebné importy                 | 1 (`ref`)      | 1 (`useState`)     | 2 (`Component`, `FormsModule`) |
| Boilerplate kód                  | **žiadny**     | wrapper funkcia + export | dekorátor `@Component` + trieda + export |
| Reaktivita                       | natívna (`ref`)| explicitná (`useState + setter`) | implicitná (cez modul) |
