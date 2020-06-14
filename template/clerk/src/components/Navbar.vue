<template>
  <div class="nav-bar" v-if="!$store.state.isWeixin">
    <van-nav-bar
      :title="changeTitle"
      :z-index="999"
      :left-arrow="isShowLeft"
      fixed
      @click-left="onClickLeft"
      @click-right="showRightMenu = !showRightMenu"
    >
      <van-icon name="v-icon-menu1" slot="right" v-if="isMenu"/>
      <slot name="slotRight" slot="right" v-if="!isMenu"></slot>
    </van-nav-bar>
  </div>
</template>

<script>
import { NavBar } from "vant";
export default {
  name: "nav-bar",
  data() {
    return {
      showRightMenu: false
    };
  },
  props: {
    title: {
      type: String
    },
    isMenu: {
      type: Boolean,
      default: false
    },
    isShowLeft: {
      type: Boolean,
      default: true
    }
  },
  computed: {
    changeTitle() {
      return this.title ? this.title : this.$route.meta.title;
    }
  },
  methods: {
    onClickLeft() {
      if (window.history.length <= 1) {
        this.$router.push("/");
        return false;
      } else {
        this.$router.back();
      }
    }
  },
  destroyed() {
    this.showRightMenu = false;
  },
  components: {
    [NavBar.name]: NavBar
  }
};
</script>


<style scoped>
.nav-bar {
  height: 46px;
}

.van-nav-bar >>> .van-icon {
  color: #666666;
}
</style>
