<template>
  <GoodsBox
    :image="image"
    :name="name"
    :price="price"
    :marketPrice="marketPrice"
    :sales="sales"
    :link="link"
  >
    <div slot="bottom" class="bottom-group">
      <div class="sales-volume">
        <span>
          <small>{{salesText}} {{sales}}</small>
        </span>
      </div>
      <div v-if="isShow">
        <van-button
          size="mini"
          type="danger"
          @click="selectGoods(id)"
          v-if="selected == 0"
          class="btn select"
          :disabled="selectDisabled"
        >挑选</van-button>
        <van-button
          size="mini"
          type="danger"
          plain
          @click="delGoods(id)"
          v-else
          class="btn del"
          :disabled="delDisabled"
        >取消</van-button>
      </div>
    </div>
  </GoodsBox>
</template>

<script>
import GoodsBox from "@/components/GoodsBox";
import { GET_SELECTGOODS, GET_DELGOOdS } from "@/api/microshop";
export default {
  data() {
    return {
      selected: 1,
      selectDisabled: false,
      delDisabled: false
    };
  },
  props: {
    id: [String, Number],
    image: String,
    name: String,
    price: [String, Number],
    marketPrice: [String, Number],
    sales: [String, Number],
    salesText: {
      type: String,
      default: "销量"
    },
    isShow: {
      type: Boolean,
      default: true
    },
    selectedgoods: [String, Number],
    link: String
  },
  watch: {
    selected(id) {
      return this.id;
    }
  },
  mounted() {
    if (this.selectedgoods) {
      this.selected = this.selectedgoods;
    } else {
      this.selected = 0;
    }
  },
  methods: {
    selectGoods(goods_id) {
      this.selectDisabled = true;
      GET_SELECTGOODS(goods_id)
        .then(res => {
          this.selected = 1;
          this.selectDisabled = false;
        })
        .catch(error => {
          this.$Toast.fail(res.message);
        });
    },
    delGoods(goods_id) {
      this.delDisabled = true;
      GET_DELGOOdS(goods_id)
        .then(res => {
          this.selected = 0;
          this.delDisabled = false;
        })
        .catch(error => {
          this.$Toast.fail(res.message);
        });
    }
  },
  components: {
    GoodsBox
  }
};
</script>
<style scoped>
.bottom-group {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sales-volume {
  color: #999999;
  font-size: 12px;
}

.bottom-group .select.btn.van-button--disabled {
  opacity: 1;
  color: #fff;
  background-color: #f44;
  border: 1px solid #f44;
}

.bottom-group .del.btn.van-button--disabled {
  opacity: 1;
  color: #f44;
  background-color: #fff;
  border: 1px solid #f44;
}
</style>
