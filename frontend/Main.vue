<template>
<div class="min-h-screen flex flex-col">
<!-- Header -->
<header class="h-16 border-b flex items-center justify-between px-6">
<h1 class="text-xl font-semibold">Evidencia hier</h1>
<nav class="flex gap-4">
<button>Domov</button>
<button>Hry</button>
<button>O projekte</button>
<button>Profil</button>
</nav>
</header>


<div class="flex flex-1">
<!-- Sidebar -->
<aside class="w-64 border-r p-4 space-y-4">
<h2 class="font-semibold">Menu</h2>
<ul class="space-y-2">
<li><button>Prehľad</button></li>
<li><button>Pridať hru</button></li>
<li><button>Moje hry</button></li>
<li><button>Štatistiky</button></li>
</ul>
</aside>


<!-- Main Content -->
<main class="flex-1 p-6 space-y-6">
<section class="border p-4 h-64">
<h3 class="font-semibold mb-2">Obsahová sekcia 1</h3>
<p>Tu bude hlavný obsah alebo tabuľky s dátami.</p>
</section>


<section class="border p-4 h-64">
<h3 class="font-semibold mb-2">Obsahová sekcia 2</h3>
<p>Miesto pre grafy, formuláre alebo štatistiky.</p>
</section>
</main>


<!-- Secondary Sidebar -->
<aside class="w-72 border-l p-4 space-y-4">
<h2 class="font-semibold">Filtre / Detaily</h2>
<div class="border p-2 h-32">Filtrovanie obsahu</div>
<div class="border p-2 h-32">Detailný prehľad</div>
</aside>
</div>


<!-- Footer -->
<footer class="h-12 border-t flex items-center justify-center">
<p>© 2025 Evidencia hier</p>
</footer>
</div>
</template>


<script setup>
// Layout zatiaľ neobsahuje logiku, slúži len ako základná kostra UI
</script>


<style scoped>
/* Zámerne bez farieb, len štruktúra */
</style>