<template>
  <div class="channel-depot bg-f8">
    <Navbar />
    <van-cell-group>
      <van-cell icon="wap-nav" title="云仓库" is-link to="/channel/depot/list" />
      <van-cell icon="todo-list-o" title="云仓库日志" is-link to="/channel/depot/log" />
    </van-cell-group>
  </div>
</template>

<script>
import sfc from "@/utils/create";
export default sfc({
  name: "channel-depot",
  data() {
    return {};
  }
});
</script>
