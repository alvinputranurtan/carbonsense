<?php
// helper untuk membuat 1 item menu sidebar
if (!function_exists('sidebar_item')) {
    function sidebar_item($id, $label, $icon, $currentPage, $baseUrl)
    {
        $isActive = ($currentPage === $id);

        $baseClasses = 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium leading-normal';
        $activeClasses = $isActive ? ' bg-[#234248] text-white'
                                   : ' hover:bg-white/10 text-white';

        $href = $baseUrl.'/index.php?page='.$id;
        $fillStyle = $isActive ? "font-variation-settings: 'FILL' 1;" : '';

        echo '<a class="'.$baseClasses.$activeClasses.'" href="'.htmlspecialchars($href).'">';
        echo '  <span class="material-symbols-outlined" style="'.$fillStyle.'">';
        echo htmlspecialchars($icon);
        echo '  </span>';
        echo '  <p>'.htmlspecialchars($label).'</p>';
        echo '</a>';
    }
}
?>

<!-- Backdrop untuk mobile -->
<div id="sidebar-backdrop"
     class="fixed inset-0 bg-black/50 z-30 hidden md:hidden"
     onclick="toggleSidebar()"></div>

<aside id="sidebar"
  class="w-64 flex-shrink-0 bg-[#111f22] p-4 flex flex-col justify-between
         fixed inset-y-0 left-0 z-40 transform -translate-x-full
         md:static md:translate-x-0 transition-transform duration-200">
  <div class="flex flex-col gap-8">
    <div class="flex gap-3 items-center px-3">
      <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
        style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBm4UEHp4eUnls75HnD1qLEqHJzZtYmsDOuTz0EiAIFHITx3G5cYeYFLFwJPi_uDfqhYnPR9cTIisRBe9stIPsWmVnZ1lXOtcIVoE2CwMhJ06yXXkqeivlX9ddTqzY4oC6vRxLFOKxVGrpcFMaaznnCbYT43OGGBclLL3Q5z5r-QY-DVoWGY4kqpodSH45w4iiE2-GSAiiTahA7Ddr9b6JhsetEx69kkkREnt_aO5r7sDpMa6cKzfmZ89D4dCg6s2p42a81e1AcAhBr");'></div>
      <div class="flex flex-col">
        <h1 class="text-white text-base font-medium leading-normal">CarbonSense</h1>
        <p class="text-[#92c0c9] text-sm">Emission Monitoring</p>
      </div>
    </div>

    <nav class="flex flex-col gap-2">
      <?php
        sidebar_item('dashboard', 'Dashboard', 'dashboard', $currentPage, $baseUrl);
sidebar_item('analytics', 'Analytics', 'analytics', $currentPage, $baseUrl);
sidebar_item('billing', 'Billing', 'credit_card', $currentPage, $baseUrl);
sidebar_item('nodes', 'Nodes', 'sensors', $currentPage, $baseUrl);
?>
    </nav>
  </div>

  <div class="flex flex-col gap-1">
    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10"
       href="<?php echo $baseUrl; ?>/index.php?page=profile">
      <span class="material-symbols-outlined text-white">account_circle</span>
      <p class="text-white text-sm">Profile</p>
    </a>
    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10"
       href="<?php echo $baseUrl; ?>/logout.php">
      <span class="material-symbols-outlined text-white">logout</span>
      <p class="text-white text-sm">Logout</p>
    </a>
  </div>
</aside>
