<template>
    <div class="lost">
        <h1 class="dd-title">no encontrado</h1>
        <p class="dd-lead">Esta página no existe, o ya no está aquí.</p>
        <RouterLink class="dd-btn dd-btn--tone lost__back" to="/">volver al inicio</RouterLink>
    </div>
</template>

<style scoped>
.lost {
    display: grid;
    justify-items: start;
    gap: 10px;
}

.lost__back {
    margin-top: 12px;
}
</style>
