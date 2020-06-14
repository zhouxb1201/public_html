<template>
  <van-cell-group class="card-group-box">
    <van-cell :title="items.title">
      <span slot="right-icon" class="value">{{items.value}}</span>
    </van-cell>
    <van-cell v-for="(item,index) in items.item" :key="index">
      <GoodsCard
        :title="item.title"
        :desc="item.desc"
        :price="item.price"
        :num="item.num"
        :thumb="item.img"
      ></GoodsCard>
    </van-cell>
    <van-cell v-if="items.operate.length">
      <van-row type="flex" justify="end" class="btn-group">
        <van-button
          class="btn"
          size="small"
          @click="click(btn)"
          v-for="(btn,index) in items.operate"
          :key="index"
        >{{btn.name}}</van-button>
      </van-row>
    </van-cell>
  </van-cell-group>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
export default {
  data() {
    return {};
  },
  props: {
    items: Object
  },
  methods: {
    click({ no }) {
      if (no == "detail") {
        this.$router.push("/order/detail/" + this.items.id);
      }
    }
  },
  components: {
    GoodsCard
  }
};
</script>

<style scoped>
.value {
  font-size: 12px;
  color: #606266;
  padding-left: 10px;
}
</style>