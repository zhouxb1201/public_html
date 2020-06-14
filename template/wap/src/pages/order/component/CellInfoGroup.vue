<template>
  <van-cell-group>
    <slot name="head" />
    <van-cell>
      <div class="item" v-for="(item,index) in columns" :key="index">
        <div
          class="title"
          :style="{whiteSpace: textAlign == 'left' ? 'initial' : 'nowrap'}"
        >{{item.title}}</div>
        <div class="value" :style="{textAlign,color:item.color,}">
          {{item.value}}
          <van-button type="danger" v-if="item.btnText" size="mini" @click="$emit('onclick',index)">{{item.btnText}}</van-button>
        </div>
      </div>
    </van-cell>
    <slot name="foot" />
  </van-cell-group>
</template>

<script>
export default {
  props: {
    columns: Array,
    textAlign: {
      type: String,
      default: "left"
    }
  }
};
</script>

<style scoped>
.item {
  width: 100%;
  display: flex;
  line-height: 24px;
  position: relative;
  background-color: #fff;
  color: #323233;
  font-size: 14px;
  overflow: hidden;
}

.item .title {
  max-width: 90px;
  flex: 1;
}

.item .value {
  flex: 1;
  text-align: left;
  color: #969799;
  overflow: hidden;
  position: relative;
  vertical-align: middle;
  font-size: 12px;
}
.item .value >>> .van-button--mini {
  min-width: 60px;
  margin-left: 10px;
}
</style>
