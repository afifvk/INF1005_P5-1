<script>
document.addEventListener('DOMContentLoaded', function () {
    const adminRibbon = document.querySelector('.admin-ribbon');
    if (adminRibbon) {
        adminRibbon.setAttribute('role', 'complementary');
        adminRibbon.setAttribute('aria-label', 'Admin mode');
    }

    const chatbot = document.getElementById('gemini-chatbot');
    if (chatbot) {
        chatbot.setAttribute('role', 'complementary');
        chatbot.setAttribute('aria-label', 'Tea Assistant');
    }

    const cartToast = document.getElementById('cart-toast');
    if (cartToast && cartToast.parentElement) {
        cartToast.parentElement.setAttribute('role', 'complementary');
        cartToast.parentElement.setAttribute('aria-label', 'Cart notifications');
    }
});
</script>
