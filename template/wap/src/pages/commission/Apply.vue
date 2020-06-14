<template>
  <Layout ref="load" class="commission-apply bg-f8">
    <Navbar :title="navbarTitle" />
    <HeadBanner :src="banner" />
    <ResultState
      :state="applyStateInfo.state"
      :message="applyStateInfo.message"
      v-if="applyStateInfo"
    />
    <div class="cell-group" v-if="isdistributor != 1">
      <ApplyFormGroup
        v-if="pageType == 1"
        :form-list="formList"
        :params="params"
        :condition-state="conditionState"
        @submit="onApply"
      />
      <ApplyConditionInfo
        v-else-if="pageType == 2"
        :title="satisfyConditionText"
        :items="conditionInfo"
      />
      <ApplyCellSubGroup
        v-else-if="pageType == 3"
        :btn-text="'成为'+$store.state.member.commissionSetText.distributor_name"
        @submit="onApply"
      />
    </div>
    <Divider
      :title="$store.state.member.commissionSetText.distributor_name+'协议'"
      :content="protocol"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import Divider from "@/components/Divider";
import ResultState from "@/components/ResultState";
import ApplyFormGroup from "./component/ApplyFormGroup";
import ApplyConditionInfo from "./component/ApplyConditionInfo";
import ApplyCellSubGroup from "./component/ApplyCellSubGroup";
import {
  GET_APPLYCOMMISSION,
  APPLY_COMMISSION,
  APPLY_REPLENISHINFO
} from "@/api/commission";
import { isEmpty } from "@/utils/util";
export default sfc({
  name: "commission-apply",
  data() {
    return {
      banner: null,
      protocol: null,

      isdistributor: null,
      condition: {},
      params: {
        user_tel: null,
        real_name: null
      },

      formList: []
    };
  },
  computed: {
    /**
     * 申请情况页面类型
     * 1 ==> 提交表单形式
     * 2 ==> 显示条件形式
     * 3 ==> 直接提交形式
     */
    pageType() {
      const isdistributor = this.isdistributor;
      const state = this.condition.distributor_condition;
      let type = 0;
      if (state == -1 || isdistributor == 3) {
        type = 1;
      } else if (state == 1 || state == 2) {
        type = 2;
      } else if (state == 3) {
        type = 3;
      }
      // 完善资料 需要提交表单
      if (this.isReplenishInfo) {
        type = 1;
      }
      // console.log(type);
      return type;
    },
    // 申请条件状态
    conditionState() {
      return this.isReplenishInfo ? -1 : this.condition.distributor_condition;
    },
    // 是否完善资料
    isReplenishInfo() {
      return this.$route.hash == "#replenish" ? true : false;
    },
    navbarTitle() {
      let title = this.isReplenishInfo
        ? "完善资料"
        : "申请成为" +
          this.$store.state.member.commissionSetText.distributor_name;
      if (title) document.title = title;
      return title;
    },
    applyStateInfo() {
      const state = this.isReplenishInfo ? 0 : this.isdistributor;
      let obj = {};
      switch (state) {
        case 1:
          obj.state = "wait";
          obj.message = "申请提交成功，请耐心等待商城审核。";
          break;
        case 2:
          obj.state = "success";
          obj.message = "已通过审核。";
          break;
        case 3:
          obj.state = "info";
          obj.message = "满足条件，请完善资料。";
          break;
        case -1:
          obj.state = "error";
          obj.message =
            "商城拒绝你成为" +
            this.$store.state.member.commissionSetText.distributor_name +
            "，请联系客服或重新提交申请。";
          break;
      }
      return isEmpty(obj) ? false : obj;
    },
    satisfyConditionText() {
      const condition = this.condition.distributor_condition;
      return condition == 1
        ? "满足以下条件自动成为" +
            this.$store.state.member.commissionSetText.distributor_name +
            ""
        : "满足其中一个条件即可成为" +
            this.$store.state.member.commissionSetText.distributor_name;
    },
    conditionInfo() {
      const $this = this;
      const condition = $this.condition.distributor_condition;
      const conditionsArr = $this.condition.distributor_conditions
        ? $this.condition.distributor_conditions.split(",")
        : [];
      let infoArr = [];
      if (condition == 1 || condition == 2) {
        conditionsArr.forEach((e, i) => {
          if (e == "2")
            infoArr.push(
              `${i + 1}：订单消费达到<span class="text">${
                $this.condition.pay_money
              }</span>元`
            );
          if (e == "3")
            infoArr.push(
              `${i + 1}：订单数达到<span class="text">${
                $this.condition.order_number
              }</span>件`
            );
          if (e == "4") infoArr.push(`${i + 1}：购买商品，并完成订单`);
          if (e == "5")
            infoArr.push(
              `${i +
                1}：购买指定商品&nbsp;&nbsp;<a class="a-link" href="/wap/goods/detail/${
                $this.condition.goods_id
              }">去购买</a>`
            );
        });
      }
      return infoArr;
    }
  },
  mounted() {
    const $this = this;
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    GET_APPLYCOMMISSION()
      .then(({ data }) => {
        $this.params.real_name = data.real_name;
        $this.params.user_tel = data.user_tel;
        $this.banner = $this.$store.state.member.commissionSetText.logo;
        $this.protocol = $this.$store.state.member.commissionSetText.content;
        $this.isdistributor = data.isdistributor;
        $this.condition = data.condition;
        $this.formList = !isEmpty(data.customform) ? data.customform : [];

        if (!$this.isReplenishInfo && data.isdistributor == 2) {
          $this.$router.replace("/commission/centre");
          $this.$refs.load.result();
        } else {
          $this.$refs.load.success();
        }
      })
      .catch(() => {
        $this.$refs.load.result();
      });
  },
  methods: {
    // 提交申请
    onApply(params) {
      const $this = this;
      // console.log(this.isReplenishInfo, params);
      // return false;
      if ($this.isReplenishInfo) {
        APPLY_REPLENISHINFO(params).then(({ message }) => {
          $this.$Toast.success(message);
          $this.$router.replace("/commission/centre");
        });
      } else {
        APPLY_COMMISSION(params).then(({ message }) => {
          $this.$Toast.success(message);
          const route =
            $this.pageType == 3 ? "/commission/centre" : "/member/centre";
          $this.$router.replace(route);
        });
      }
    }
  },
  components: {
    HeadBanner,
    Divider,
    ResultState,
    ApplyFormGroup,
    ApplyConditionInfo,
    ApplyCellSubGroup
  }
});
</script>

<style scoped>
.protocol {
  background: #ffffff;
}

.protocol .protocol-head {
  text-align: center;
  margin: 0 5%;
  position: relative;
  padding: 15px 0;
}

.protocol .protocol-head::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  border: 0 solid #eee;
  transform-origin: 0 0;
  pointer-events: none;
  width: 100%;
  border-top-width: 1px;
}

.protocol .protocol-head span {
  background: #fff;
  padding: 2px 10px;
  position: relative;
  z-index: 1;
  color: #666;
  letter-spacing: 1px;
}

.protocol .protocol-content {
  padding: 15px;
}

.cell-group {
  background: #fff;
  margin: 10px 0;
}

.condition-item >>> .text {
  padding: 0 4px;
  color: #ff454e;
}
</style>
