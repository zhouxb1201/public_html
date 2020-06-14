<template>
  <van-cell class="item">
    <GoodsCard :title="items.goods_name" :thumb="items.goods_img | BASESRC">
      <div slot="desc" class="desc text-secondary">
        <div>库存{{items.stock}}</div>
        <div>销量{{items.sales}}</div>
      </div>
      <div slot="bottom" class="price">
        <div class="card__price">售价：{{items.price | yuan}}</div>
      </div>
      <div slot="footer" class="footer" v-if="items.operate.length">
        <van-button
          class="btn"
          type="danger"
          size="mini"
          plain
          v-for="(btn,index) in items.operate"
          :key="index"
          @click="btnClick(btn)"
        >{{btn.text}}</van-button>
      </div>
    </GoodsCard>
  </van-cell>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import { SET_STOREGOODS } from "@/api/goods";
export default {
  data() {
    return {};
  },
  props: {
    items: Object
  },
  methods: {
    btnClick(item) {
      if (item.type == "Edit" || item.type == "Add") {
        this["click" + item.type](this.items.goods_id);
      } else {
        this.$Dialog
          .confirm({
            message: `确定${item.text}该商品吗？`
          })
          .then(() => {
            SET_STOREGOODS(item.type, this.items.goods_id)
              .then(({ data, message }) => {
                this.$Toast.success(message);
                this.$emit("btn-click", { ...item, id: this.items.goods_id });
              })
              .catch(() => {});
          })
          .catch(() => {});
      }
    },
    clickEdit(goodsid) {
      this.$router.push({
        name: "goods-edit",
        params: { goodsid }
      });
    },
    clickAdd(goodsid) {
      this.$router.push({
        name: "goods-edit",
        params: { goodsid },
        hash: "#add"
      });
    }
  },
  components: {
    GoodsCard
  }
};
</script>

<style scoped>
.item {
  background: #fff;
  padding: 5px;
}

.desc {
  display: flex;
}

.desc > div {
  margin-right: 10px;
}

.tags {
  display: flex;
}

.footer {
  padding-top: 5px;
  padding-left: 100px;
  display: flex;
  justify-content: flex-start;
}
</style>