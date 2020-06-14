<template>
  <div class="items">
    <div class="item" v-for="(item,index) in filterList" :key="index">
      <img :src="item.user_headimg | BASESRC" v-if="item.buyer_id" :onerror="$ERRORPIC.noAvatar">
      <span class="help" v-else>?</span>
      <van-tag round type="danger" v-if="item.is_head">团长</van-tag>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    list: {
      type: Array,
      default: []
    },
    group_num: {
      type: [String, Number]
    }
  },
  computed: {
    filterList() {
      const num = this.group_num > 5 ? 5 : this.group_num
      let list = this.list.filter((e, i) => i < 5);
      let diff = num - list.length;
      let arr = new Array(diff).fill({ fill: true });
      arr.forEach(e => {
        list.push(e);
      });
      return list;
    }
  }
};
</script>
<style scoped>
.items {
  display: flex;
  justify-content: space-around;
}

.items .item {
  position: relative;
  width: 50px;
  height: 50px;
}

.items .item img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: block;
}

.items .item span.van-tag {
  position: absolute;
  right: 0;
  top: 0;
}

.items .help {
  display: block;
  width: 50px;
  height: 50px;
  line-height: 50px;
  font-size: 30px;
  color: #999;
  border-radius: 50%;
  border: 1px dashed #999;
  text-align: center;
}
</style>

