<template>
  <Layout ref="load" class="microshop-gradecentre">
    <Navbar/>
    <div class="content">
      <div class="bg-f8 head-wrap">
        <div class="head">
          <h4>{{shopkeeper_level_name}}</h4>
          <div
            class="time"
          >{{shopkeeper_level_time == '无期限' ? shopkeeper_level_time : '将于'+shopkeeper_level_time+ '到期'}}</div>
          <div class="btn-wrap">
            <van-button
              round
              size="small"
              type="default"
              @click="onRenew"
              v-if="is_default_shopkeeper == 0"
            >立即续费</van-button>
            <van-button round size="small" type="default" @click="onUpGrade">提升等级</van-button>
          </div>
        </div>
      </div>

      <div class="grade-introduce">
        <h4>等级权益介绍</h4>
        <div class="grade-table">
          <ul class="grade-th">
            <li>
              等级
              <p class="bd-left"></p>
            </li>
            <li>专属店铺</li>
            <li>自购返利</li>
            <li>开店返利</li>
          </ul>
          <div class="table-wrap">
            <div class="table">
              <ul v-for="(item , index) in items" :key="index" :style="styles">
                <li>{{item.level_name}}</li>
                <li>拥有</li>
                <li>
                  <span v-if="item.selfpurchase_rebate > 0">{{item.selfpurchase_rebate | numbers}}%</span>
                  <span v-else>无</span>
                </li>
                <li>
                  <span v-if="item.shop_rebate > 0">{{item.shop_rebate | numbers}}%</span>
                  <span v-else>无</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <div class="tips">
          <p>
            <span>店主升级：店主通过购买升级礼包升级。</span>
          </p>
          <p>
            <span>店主降级：等级过期后，将会降至默认等级。</span>
          </p>
          <p>
            <span>专属店铺：拥有自己的专属微店。</span>
          </p>
          <p>
            <span>自购返利：店主在平台消费，获得返利。</span>
          </p>
          <p>
            <span>销售返利：消费者通过店主微店消费，获得返利。</span>
          </p>
          <p>
            <span>开店返利：下线开店获得相应返利。</span>
          </p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_GRADEINFO, GET_UPGRADE } from "@/api/microshop";
import { isEmpty } from "@/utils/util";
import { getSession } from "@/utils/storage";
export default sfc({
  name: "microshop-gradecentre",
  data() {
    return {
      items: null
    };
  },
  computed: {
    info() {
      return this.$store.state.microshop.info;
    },
    shopkeeper_level_name() {
      const { info } = this;
      return info && info.shopkeeper_level_name
        ? info.shopkeeper_level_name
        : " ";
    },
    shopkeeper_level_time() {
      const { info } = this;
      return info && info.shopkeeper_level_time
        ? info.shopkeeper_level_time
        : "";
    },
    is_default_shopkeeper() {
      const { info } = this;
      return info && info.is_default_shopkeeper
        ? info.is_default_shopkeeper
        : "";
    },
    shopkeeper_id() {
      const { info } = this;
      return info && info.uid ? info.uid : "";
    },
    styles() {
      if (this.items.length > 3) {
        return { flexShrink: "0", width: "90px" };
      } else {
        return { flex: "auto" };
      }
    }
  },
  filters: {
    numbers(val) {
      if (val.indexOf !== -1) {
        //判断是否有小数点
        let n = val.split(".");
        if (n[1] == "00") {
          //判断小数点后两位是否为00
          return n[0];
        } else {
          return val;
        }
      } else {
        return val;
      }
    }
  },
  mounted() {
    this.$store
      .dispatch("getMicroshopInfo")
      .then(res => {
        this.loadData();
        this.$refs.load.success();
      })
      .catch(error => {
        this.$refs.load.fail();
      });
  },
  methods: {
    loadData() {
      GET_GRADEINFO().then(({ data }) => {
        this.items = data;
        this.$refs.load.success();
      });
    },
    onRenew() {
      //立即续费
      this.$router.replace({
        name: "microshop-centre",
        query: {
          order_type: 3
        }
      });
    },
    onUpGrade() {
      GET_UPGRADE().then(({ data }) => {
        if (data.length == 0) {
          this.$Toast("您当前已是最高等级");
          return false;
        }
        //立即升级
        this.$router.replace({
          name: "microshop-centre",
          query: {
            order_type: 4
          }
        });
      });
    }
  }
});
</script>

<style scoped>
.content {
  padding-bottom: 70px;
}
.head-wrap {
  overflow: hidden;
  position: relative;
}
.head {
  position: relative;
  overflow: hidden;
  height: 130px;
  background: rgba(255, 153, 0, 1);
  margin: 10px;
  border-radius: 10px;
  -webkit-box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  color: #fff;
}
.head h4 {
  font-size: 18px;
  font-weight: normal;
  margin: 20px;
}
.head div.time {
  font-size: 12px;
  margin-left: 20px;
}
.btn-wrap {
  float: right;
  margin-right: 10px;
  margin-top: 20px;
  position: relative;
}
.btn-wrap >>> .van-button--default {
  color: #fff;
  background-color: transparent;
  margin-left: 5px;
}
.btn-wrap >>> .van-button--small {
  padding: 0 20px;
}
/****GRADE--TABLE****/

.grade-introduce {
  position: relative;
}
.grade-introduce h4 {
  font-size: 14px;
  font-weight: normal;
  margin: 10px;
}
.grade-table {
  justify-content: center;
  position: relative;
  display: flex;
}
.grade-th {
  width: 25%;
  position: relative;
  overflow: hidden;
  color: #cc6600;
}
.grade-table .grade-th:after {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #e4e4e4;
  transform: scaleX(0.5);
}

.grade-table .table {
  position: relative;
  display: flex;
  overflow-x: auto;
  overflow-y: hidden;
}
.grade-table .table ul {
  position: relative;
}
.grade-table .table li,
.grade-th li {
  text-align: center;
  height: 30px;
  line-height: 30px;
  position: relative;
}

.grade-table .table ul li:first-child,
.grade-th li:first-child {
  color: #cc6600;
  background-color: #ffcc99;
}

.grade-table .table ul:after {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #e4e4e4;
  transform: scaleX(0.5);
}
.grade-table .table ul li:first-child:before,
.grade-th li:first-child:before {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #cc6600;
  transform: scaleX(0.5);
  z-index: 99;
}
.grade-table .table ul li:after,
.grade-th li:after {
  content: "";
  position: absolute;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 1px;
  background-color: #e4e4e4;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
}
.grade-table .table ul:before,
.grade-th:before {
  content: "";
  position: absolute;
  right: 0;
  top: 0;
  width: 100%;
  height: 1px;
  background-color: #cc6600;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  z-index: 99;
}
.bd-left {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 1px;
  background-color: #cc6600;
  transform: scaleX(0.5);
  z-index: 99;
}
.grade-introduce .tips {
  position: relative;
  margin: 10px;
  font-size: 12px;
  color: #ed5565;
  line-height: 18px;
}
/***grade table scroll***/
.table-wrap {
  width: 75%;
  overflow-y: hidden;
}
</style>

