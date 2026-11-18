// Livewire supplies Alpine. Do not load a second copy of Alpine here.
document.addEventListener("livewire:navigated", () => {
    document.documentElement.lang = "id";
});
