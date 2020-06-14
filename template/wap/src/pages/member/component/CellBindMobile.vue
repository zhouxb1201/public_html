<template>
  <van-cell-group
    class="card-group-box"
    v-if="$store.getters.isBingFlag&&!$store.getters.isBindMobile"
  >
    <van-cell
      class="cell"
      value-class="cell-value"
      is-link
      center
      @click="$BindMobile.open()"
      :style="cellGroupStyle"
    >
      <div class="img">
        <van-icon name="v-icon-bind-phone" size="32px" :color="info.style.iconcolor" />
      </div>
      <div class="text">
        <div :style="titleStyle">{{info.params.title}}</div>
        <div class="tip" :style="descStyle">{{info.params.desc}}</div>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    info: Object
  },
  computed: {
    cellGroupStyle() {
      return {
        background: this.info.style.background
      };
    },
    titleStyle() {
      return {
        color: this.info.style.titlecolor
      };
    },
    descStyle() {
      return {
        color: this.info.style.desccolor
      };
    }
  }
};
</script>

<style scoped>
.cell-value {
  display: flex;
}

.img {
  display: flex;
  justify-content: center;
  align-items: center;
  padding-right: 10px;
}

.text {
  flex: auto;
}

.text .tip {
  color: #909399;
  font-size: 10px;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
</style>

