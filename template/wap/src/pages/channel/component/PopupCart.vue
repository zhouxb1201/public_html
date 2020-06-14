<template>
  <PopupBottom v-model="show" title="已选商品">
    <van-cell-group class="list" :border="false" v-if="list.length>0">
      <van-cell class="item" v-for="(item,index) in list" :key="index">
        <GoodsCard class="goods-card" :desc="item.sku_name" :thumb="item.goods_picture | BASESRC">
          <div slot="title" class="info-head">
            <div class="name">{{item.goods_name}}</div>
            <div
              class="price letter-price"
              v-if="$route.params.type == 'purchase'"
            >{{item.price | yuan}}</div>
          </div>
          <div slot="bottom" class="info-foot">
            <van-stepper
              v-model="item.num"
              integer
              disable-input
              :max="item.max_buy"
              :async-change="true"
              @overlimit="onOverlimit"
              @plus="onChange('plus',item)"
              @minus="onChange('minus',item)"
            />
            <div class="del e-handle" @click="onRemove(item.sku_id)">
              <van-icon name="delete" />
            </div>
          </div>
        </GoodsCard>
      </van-cell>
    </van-cell-group>
    <LoadMoreEnd finished text-background="#ffffff" v-else />
    <div slot="footer" class="sku-action-group">
      <van-button
        class="action-btn"
        type="danger"
        round
        block
        :disabled="isDisabled"
        @click="onSubmit"
      >{{btnText}}</van-button>
    </div>
  </PopupBottom>
</template>

<script>
import { Stepper } from "vant";
import GoodsCard from "@/components/GoodsCard";
import LoadMoreEnd from "@/components/LoadMoreEnd";
import PopupBottom from "@/components/PopupBottom";
import { isEmpty } from "@/utils/util";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    list: {
      type: Array
    }
  },
  computed: {
    show: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    },
    btnText() {
      let text = "";
      // 采购情况需要显示最小采购金额
      if (this.$route.params.type == "purchase") {
        text =
          !this.$store.state.channel.isAchieveCondie &&
          this.$store.state.channel.message
            ? this.$store.state.channel.message
            : `结算(合计：${yuan(this.totalPrice)})`;
      } else {
        text = "提货";
      }
      return text;
    },
    totalPrice() {
      return parseFloat(this.$store.state.channel.total_money);
    },
    isDisabled() {
      let flag = true;
      if (this.$route.params.type == "purchase") {
        flag = !this.$store.state.channel.isAchieveCondie || isEmpty(this.list);
      } else {
        flag = isEmpty(this.list);
      }
      return flag;
    }
  },
  methods: {
    onClose() {
      this.$emit("input", false);
    },
    onChange(action, data) {
      const $this = this;
      const params = {};
      params.sku_id = data.sku_id;
      params.num = data.num;
      params.channel_info = data.channel_info;
      params.buy_type = $this.$route.params.type;
      // console.log(params);
      $this.$store.dispatch("editCartGoodsNum", params).then(() => {
        $this.$store.dispatch("getChannelCartList", {
          page_index: 1,
          page_size: 20,
          buy_type: $this.$route.params.type
        });
      });
    },
    onOverlimit(action) {
      if (action === "plus") {
        this.$Toast("库存不足！");
      } else {
        this.$Toast("至少选择一件！");
      }
    },
    onRemove(sku_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定删除该商品？"
        })
        .then(() => {
          $this.$store
            .dispatch("removeChannelCart", {
              sku_id,
              buy_type: $this.$route.params.type
            })
            .then(({ message }) => {
              $this.$Toast.success(message);
              setTimeout(() => {
                $this.$store
                  .dispatch("getChannelCartList", {
                    page_index: 1,
                    page_size: 20,
                    buy_type: $this.$route.params.type
                  })
                  .then(({ data }) => {
                    isEmpty(data.cart_list) && $this.onClose();
                  });
              }, 500);
            });
        });
    },
    onSubmit() {
      const $this = this;
      const type = $this.$route.params.type;
      $this.onClose();
      setTimeout(() => {
        $this.$router.push({
          name: "channel-order-confirm",
          params: {
            type
          }
        });
      }, 100);
    }
  },
  components: {
    GoodsCard,
    LoadMoreEnd,
    [Stepper.name]: Stepper,
    PopupBottom
  }
};
</script>
<style scoped>
.goods-card {
  background: #fff;
  padding: 0;
}

.info-head {
  width: 100%;
  display: flex;
}

.info-head .name {
  max-height: 34px;
  font-weight: bold;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  height: 32px;
  line-height: 17px;
  flex: 1;
  position: relative;
}

.info-head .price {
  color: #ff454e;
  font-weight: bold;
  line-height: 1.2;
  padding-left: 4px;
}

.info-foot {
  width: 100%;
  display: flex;
  justify-content: space-between;
}

.info-foot .del {
  display: flex;
  padding: 7px;
  font-size: 16px;
}

.info-foot >>> .van-stepper__input[disabled] {
  color: #323233;
}
</style>
