<template>
  <div>
    <van-cell
      :title="title"
      :title-class="title != '推荐商品' ? color.yes : color.no"
      :value="text"
      is-link
      @click="onGoods"
      v-if="is_code"
    >
      <van-icon
        slot="icon"
        :style="{backgroundColor:title != '推荐商品' ? '#6790db' : '#cccccc'}"
        class="cell-left-icon"
        name="cart"
        size="12px"
      />
    </van-cell>
    <van-popup v-model="showList" position="bottom" class="goods-popup">
      <van-nav-bar
        title="推荐商品"
        left-text="返回"
        left-arrow
        fixed
        :z-index="999"
        @click-left="showList = false"
      />
      <div class="goods-list">
        <van-cell
          v-for="(item,index) in goods_list"
          :key="index"
          @click="toggle(index,item.goods_id)"
        >
          <div class="goods-item">
            <div class="goods-checkbox">
              <van-checkbox v-model="item.checked" ref="checkboxes" />
            </div>
            <div class="goods-card">
              <img :src="item.goods_img" :onerror="$ERRORPIC.noGoods" />
              <div class="content">
                <div class="title">{{item.goods_name}}</div>
                <div class="bottom">
                  <span>￥{{item.price}}</span>
                  <span>￥{{item.market_price}}</span>
                </div>
              </div>
            </div>
          </div>
        </van-cell>
      </div>
      <div class="btn-bottom">
        <van-button round type="danger" size="normal" @click="onRecommend">推荐</van-button>
      </div>
    </van-popup>
  </div>
</template>

<script>
import { NavBar } from "vant";
import { GET_RECOMMENDGOODSLIST } from "@/api/thingcircle";
export default {
  data() {
    return {
      showList: false,
      goods_list: [],
      title: "推荐商品",
      text: "推荐购买过的商品",
      color: {
        yes: "cr-ff454e",
        no: "cr-323233"
      },
      is_code: 0
    };
  },
  mounted() {
    this.getGoodsList();
  },
  methods: {
    getGoodsList() {
      const $this = this;
      GET_RECOMMENDGOODSLIST().then(res => {
        if (res.code == 0) {
          //未开启推荐商品功能
          $this.is_code = 0;
        } else if (res.code == 1) {
          $this.goods_list = res.data;
          $this.is_code = 1;
        } else if (res.code == 2) {
          //没有推荐商品
          $this.is_code = 2;
        }
      });
    },
    toggle(index, id) {
      this.$refs.checkboxes[index].toggle();
    },
    onRecommend() {
      let arry_goodsId = [];
      for (let i = 0; i < this.goods_list.length; i++) {
        if (this.goods_list[i].checked == true) {
          arry_goodsId.push(this.goods_list[i].goods_id);
        }
      }
      this.text = arry_goodsId.length > 0 ? "" : "推荐购买过的商品";
      this.title =
        arry_goodsId.length > 0
          ? "已选中" + arry_goodsId.length + "件商品"
          : "推荐商品";
      this.showList = false;
      this.$emit("arr-goodsId", arry_goodsId.join());
    },
    onGoods() {
      const $this = this;
      if ($this.is_code == 1) {
        $this.showList = true;
      } else if ($this.is_code == 2) {
        $this.$Toast("没有推荐商品");
      }
    }
  },
  components: {
    [NavBar.name]: NavBar
  }
};
</script>

<style scoped>
.goods-popup {
  height: 100%;
  padding-top: 46px;
  border-radius: 0;
}
.goods-list {
  height: 100%;
  box-sizing: border-box;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
}
.goods-item {
  display: flex;
}
.goods-item .goods-checkbox {
  display: flex;
  align-items: center;
  margin-right: 10px;
  width: 20px;
}
.goods-item .goods-card {
  flex: 1;
  position: relative;
  color: #323233;
  font-size: 12px;
  box-sizing: border-box;
  background-color: #fff;
  display: flex;
}
.goods-item .goods-card img {
  width: 50px;
  height: 50px;
  display: block;
  margin-right: 10px;
}
.goods-item .goods-card .content {
  position: relative;
  -ms-flex: 1;
  flex: 1;
  height: 50px;
}
.goods-item .goods-card .content .title {
  height: 32px;
  line-height: 16px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-all;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 12px;
  font-weight: 700;
}
.goods-item .goods-card .content .bottom {
  width: 100%;
  line-height: 18px;
  margin-top: 2px;
}
.goods-item .goods-card .content .bottom span:first-child {
  display: inline-block;
  color: #ff454e;
  font-weight: 700;
  margin-right: 10px;
}
.goods-item .goods-card .content .bottom span:last-child {
  display: inline-block;
  color: #999;
  font-weight: 700;
  text-decoration: line-through;
}
.btn-bottom {
  height: 50px;
  box-sizing: border-box;
}
.btn-bottom >>> .van-button {
  width: 90%;
  position: fixed;
  bottom: 10px;
  left: 5%;
  z-index: 999;
}
.cr-323233 {
  color: #323233;
}
.cr-ff454e {
  color: #6790db;
}
.cell-left-icon {
  height: 18px;
  line-height: 18px;
  margin-right: 5px;
  background-color: #cccccc;
  border-radius: 50%;
  padding: 0px 3px;
  color: #fff;
  margin-top: 3px;
}
</style>