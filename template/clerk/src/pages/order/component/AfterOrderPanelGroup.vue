<template>
  <van-cell-group class="item card-group-box" :border="false">
    <van-cell :title="items.title">
      <span slot="right-icon">{{items.value}}</span>
    </van-cell>
    <van-cell v-for="(item,index) in items.item" :key="index">
      <GoodsCard
        class="goods-card"
        lazyLoad
        :num="item.num"
        :price="item.price"
        :desc="item.desc"
        :title="item.title"
        :thumb="item.img"
      >
        <div slot="title" class="title">
          <div class="titie-name">{{item.title}}</div>
          <div class="titie-right-text">{{item.value}}</div>
        </div>
        <van-row
          type="flex"
          justify="end"
          class="btn-group"
          slot="footer"
          v-if="item.operate.length||item.operateText"
        >
          <div class="operate-text" v-if="item.operateText">{{item.operateText}}</div>
          <van-button
            v-else
            size="mini"
            class="btn"
            v-for="(btn,index) in item.operate"
            :key="index"
            @click="btnClick(btn,{order_goods_id:item.id,order_id:items.id})"
          >{{btn.name}}</van-button>
        </van-row>
      </GoodsCard>
    </van-cell>
    <van-cell>
      <van-row type="flex" justify="end">
        <van-col>
          <span>{{items.moneyText}}</span>
          <span class="pay-money-text">{{items.money | yuan}}</span>
        </van-col>
      </van-row>
    </van-cell>
    <van-cell v-if="items.operate.length||items.operateText">
      <van-row type="flex" justify="end">
        <div class="operate-text" v-if="items.operateText">{{items.operateText}}</div>
        <van-button
          v-else
          size="small"
          class="btn"
          v-for="(btn,index) in items.operate"
          :key="index"
          @click="btnClick(btn,{order_id:items.id})"
        >{{btn.name}}</van-button>
      </van-row>
    </van-cell>
  </van-cell-group>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import { AGREE_AFTERORDER, REFUSE_AFTERORDER } from "@/api/order";
export default {
  data() {
    return {};
  },
  props: {
    items: {
      type: Object
    }
  },
  methods: {
    btnClick(item, params) {
      // console.log(item, params);
      this.$Dialog
        .confirm({
          message: "确认" + item.name + "?"
        })
        .then(() => {
          if (item.no == "agree_refund") {
            AGREE_AFTERORDER(params).then(({ message }) => {
              this.$emit("init-list", message);
            });
          } else if (item.no == "refuse") {
            REFUSE_AFTERORDER(params).then(({ message }) => {
              this.$emit("init-list", message);
            });
          }
        })
        .catch(() => {});
    }
  },
  components: {
    GoodsCard
  }
};
</script>
<style scoped>
.goods-card {
  padding: 0;
  background: #ffffff;
}

.title {
  display: flex;
  justify-content: space-between;
}

.title .titie-name {
  max-height: 40px;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  height: 40px;
  line-height: 20px;
  -ms-flex: 1;
  flex: 1;
  position: relative;
}

.title .titie-right-text {
  color: #ff454e;
  font-weight: 700;
  line-height: 1.2;
  padding-left: 4px;
}

.pay-type-text {
  color: #909399;
  font-size: 12px;
  padding-right: 10px;
}

.pay-money-text {
  color: #ff454e;
  padding-left: 6px;
}

.operate-text {
  color: #ff454e;
  text-align: right;
  font-size: 12px;
}

.btn-group {
  margin-top: 5px;
}

.btn {
  margin-left: 5px;
  width: auto;
  padding: 0 6px;
}
</style>
