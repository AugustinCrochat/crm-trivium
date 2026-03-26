    </main>
  </div>
</div>

<script>
function openMobile() {
  document.getElementById('sidebar').classList.remove('-translate-x-full');
  document.getElementById('overlay').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeMobile() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('overlay').classList.add('hidden');
  document.body.style.overflow = '';
}
</script>
</body>
</html>
